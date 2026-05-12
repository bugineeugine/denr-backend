<?php

namespace App\Repositories\Implementations;

use App\Models\Violation;
use App\Repositories\ViolationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ViolationRepository implements ViolationRepositoryInterface
{
    public function getAllViolations()
    {
        return Violation::with(['permit:id,permit_no,permit_type,created_by,status', 'recorder:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data)
    {
        return Violation::create($data);
    }

    public function findViolationById(string $id)
    {
        return Violation::with(['permit:id,permit_no,permit_type,created_by,status', 'recorder:id,name,email'])
            ->find($id);
    }

    public function findAndUpdateViolationById(string $id, array $data)
    {
        $violation = Violation::find($id);
        if (!$violation) {
            return null;
        }
        $violation->update($data);
        return $violation->fresh(['permit', 'recorder']);
    }

    public function findAndDeleteViolationById(string $id)
    {
        $violation = Violation::find($id);
        if (!$violation) {
            return null;
        }
        $violation->delete();
        return $violation;
    }

    public function getViolationsByPermitId(string $permitId)
    {
        return Violation::with('recorder:id,name,email')
            ->where('permit_id', $permitId)
            ->orderBy('date_recorded', 'desc')
            ->get();
    }

    public function getViolationStats()
    {
        return [
            'total' => Violation::count(),
            'open' => Violation::where('status', 'Open')->count(),
            'resolved' => Violation::where('status', 'Resolved')->count(),
            'thisMonth' => Violation::whereBetween('created_at', [now()->startOfMonth(), now()])->count(),
            'bySeverity' => Violation::selectRaw('severity, COUNT(*) as total')
                ->groupBy('severity')
                ->get(),
            'byType' => Violation::selectRaw('violation_type, COUNT(*) as total')
                ->groupBy('violation_type')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
        ];
    }

    public function getTopViolatorLocations(int $limit = 5)
    {
        return Violation::selectRaw('location, COUNT(*) as total, AVG(lat) as lat, AVG(lng) as lng')
            ->whereNotNull('location')
            ->groupBy('location')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function getReports(?string $from = null, ?string $to = null)
    {
        $query = Violation::with(['permit:id,permit_no,permit_type', 'recorder:id,name']);

        if ($from) {
            $query->whereDate('date_recorded', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date_recorded', '<=', $to);
        }

        return $query->orderBy('date_recorded', 'desc')->get();
    }
}
