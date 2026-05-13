<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PermitRepositoryInterface;
use App\Repositories\ViolationRepositoryInterface;
use App\Models\Permit;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $permits;
    protected $violations;

    public function __construct(PermitRepositoryInterface $permits, ViolationRepositoryInterface $violations){
        $this->permits = $permits;
        $this->violations = $violations;
    }

    public function index()
    {
        try {
            $stats = $this->permits->getAllPermitsDashobard();
            $stats['violationStats'] = $this->violations->getViolationStats();
            $stats['topViolatorLocations'] = $this->violations->getTopViolatorLocations(5);
            $stats['dssAlerts'] = $this->buildDssAlerts($stats['violationStats']);
            $stats['expiredPermitCount'] = Permit::where('status', 'Expired')->count();
            $stats['expiringSoonCount'] = $this->countExpiringSoon(30);

            return response()->json([
                'message' => 'Dashboard data retrieved successfully!',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                 'data' => []
            ], 500);
        }
    }

    private function countExpiringSoon(int $days): int
    {
        $today = Carbon::today();
        $threshold = $today->copy()->addDays($days);
        $count = 0;
        Permit::where('status', 'Approved')->select('expiry_date')->chunk(500, function ($rows) use (&$count, $today, $threshold) {
            foreach ($rows as $row) {
                try {
                    $exp = Carbon::createFromFormat('m/d/Y', $row->expiry_date);
                    if ($exp->between($today, $threshold)) {
                        $count++;
                    }
                } catch (\Exception $e) { /* skip */ }
            }
        });
        return $count;
    }

    private function buildDssAlerts(array $vStats): array
    {
        $alerts = [];
        $today = Carbon::today();

        // ── Per-permit expiry alerts (within next 7 days) ───────────
        $expiringSoon = [];
        Permit::where('status', 'Approved')
            ->select('permit_no', 'expiry_date')
            ->chunk(500, function ($rows) use (&$expiringSoon, $today) {
                foreach ($rows as $row) {
                    try {
                        $exp = Carbon::createFromFormat('m/d/Y', $row->expiry_date);
                        $diff = (int) $today->diffInDays($exp, false);
                        if ($diff >= 0 && $diff <= 7) {
                            $expiringSoon[] = ['permit_no' => $row->permit_no, 'days' => $diff];
                        }
                    } catch (\Exception $e) { /* skip */ }
                }
            });

        // Sort soonest first
        usort($expiringSoon, fn ($a, $b) => $a['days'] <=> $b['days']);

        // Bulk summary
        if (count($expiringSoon) >= 1) {
            $alerts[] = [
                'type'    => 'expiring_soon',
                'level'   => 'warning',
                'title'   => 'Permits expiring soon',
                'message' => count($expiringSoon) . ' permit(s) will expire within 7 days.',
                'count'   => count($expiringSoon),
            ];
        }

        // Per-permit specific alerts (top 5 most urgent)
        foreach (array_slice($expiringSoon, 0, 5) as $row) {
            $when = $row['days'] === 0
                ? 'today'
                : ($row['days'] === 1 ? 'tomorrow' : "in {$row['days']} days");
            $alerts[] = [
                'type'      => 'permit',
                'permit_no' => $row['permit_no'],
                'level'     => $row['days'] <= 1 ? 'critical' : 'warning',
                'title'     => "Permit {$row['permit_no']} expires {$when}",
                'message'   => "Notify the applicant to renew before expiration.",
            ];
        }

        // ── Expired permits ─────────────────────────────────────────
        $expiredPermits = Permit::where('status', 'Expired')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->pluck('permit_no')
            ->all();
        if (!empty($expiredPermits)) {
            $total = Permit::where('status', 'Expired')->count();
            $alerts[] = [
                'type'    => 'expired',
                'level'   => 'high',
                'title'   => "{$total} expired permit(s)",
                'message' => 'Recent: ' . implode(', ', $expiredPermits),
                'count'   => $total,
            ];
        }

        // ── Suspended permits (per-permit) ──────────────────────────
        $suspended = Permit::where('status', 'Suspended')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->pluck('permit_no')
            ->all();
        foreach ($suspended as $no) {
            $alerts[] = [
                'type'      => 'permit',
                'permit_no' => $no,
                'level'     => 'high',
                'title'     => "Permit {$no} suspended",
                'message'   => 'Active violation pending review.',
            ];
        }

        // ── Open violations ─────────────────────────────────────────
        $open = $vStats['open'] ?? 0;
        if ($open >= 5) {
            $alerts[] = [
                'type'    => 'open_violations',
                'level'   => 'high',
                'title'   => "{$open} open violations",
                'message' => 'Unresolved cases need investigation.',
                'count'   => $open,
            ];
        }

        return $alerts;
    }

    /**
     * Return the full list of permits behind a DSS alert.
     * Types: expiring_soon | expired | suspended | open_violations | permit
     */
    public function dssDetails(Request $request, string $type)
    {
        try {
            $payload = [];

            if ($type === 'permit') {
                $permitNo = $request->query('permit_no');
                if (!$permitNo) {
                    return response()->json(['message' => 'permit_no is required'], 422);
                }
                $permit = Permit::with('creator:id,name,email')
                    ->where('permit_no', $permitNo)
                    ->first();
                $payload = $permit ? [$permit] : [];
            }
            elseif ($type === 'expired') {
                $payload = Permit::with('creator:id,name,email')
                    ->where('status', 'Expired')
                    ->orderByDesc('updated_at')
                    ->get();
            }
            elseif ($type === 'suspended') {
                $payload = Permit::with('creator:id,name,email')
                    ->where('status', 'Suspended')
                    ->orderByDesc('updated_at')
                    ->get();
            }
            elseif ($type === 'expiring_soon') {
                $today = Carbon::today();
                $found = collect();
                Permit::with('creator:id,name,email')
                    ->where('status', 'Approved')
                    ->chunk(500, function ($rows) use (&$found, $today) {
                        foreach ($rows as $row) {
                            try {
                                $exp = Carbon::createFromFormat('m/d/Y', $row->expiry_date);
                                $diff = (int) $today->diffInDays($exp, false);
                                if ($diff >= 0 && $diff <= 7) {
                                    $row->days_until_expiry = $diff;
                                    $found->push($row);
                                }
                            } catch (\Exception $e) { /* skip */ }
                        }
                    });
                $payload = $found->sortBy('days_until_expiry')->values();
            }
            elseif ($type === 'open_violations') {
                // Return the permits that have open/investigating violations
                $permitIds = \App\Models\Violation::whereIn('status', ['Open', 'Investigating'])
                    ->whereNotNull('permit_id')
                    ->pluck('permit_id')
                    ->unique()
                    ->all();
                $payload = Permit::with('creator:id,name,email')
                    ->whereIn('id', $permitIds)
                    ->orderByDesc('updated_at')
                    ->get();
            }
            else {
                return response()->json(['message' => 'Unknown DSS alert type'], 404);
            }

            return response()->json([
                'message' => 'Retrieve successfully!',
                'data'    => $payload,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
      public function permitUserById(string $userId)
    {
        try {
            $stats = $this->permits->getAllPermitsDashboardByUser($userId);

            return response()->json([
                'message' => 'Dashboard data retrieved successfully!',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'message' => $e->getMessage(),
                 'data' => []
            ], 500);
        }
    }
}
