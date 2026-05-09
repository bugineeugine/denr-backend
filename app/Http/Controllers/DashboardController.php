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

        if (($vStats['open'] ?? 0) >= 5) {
            $alerts[] = [
                'level' => 'high',
                'title' => 'High open violations',
                'message' => "{$vStats['open']} unresolved violations need attention.",
            ];
        }

        $expiredCount = Permit::where('status', 'Expired')->count();
        if ($expiredCount >= 3) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Frequent expired permits',
                'message' => "{$expiredCount} permits are currently expired and may need renewal outreach.",
            ];
        }

        $suspendedCount = Permit::where('status', 'Suspended')->count();
        if ($suspendedCount >= 1) {
            $alerts[] = [
                'level' => 'high',
                'title' => 'Suspended permits',
                'message' => "{$suspendedCount} permit(s) currently suspended pending violation review.",
            ];
        }

        return $alerts;
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
