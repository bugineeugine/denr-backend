<?php

namespace App\Repositories\Implementations;

use App\Models\Permit;
use App\Repositories\PermitRepositoryInterface;

class PermitRepository implements PermitRepositoryInterface
{

    public function getAllPermits()
    {
      return Permit::with('creator:id,name,email')->orderBy('created_at', 'desc')->get();
    }
     public function create(array $data)
    {
        return Permit::create($data);
    }
    public function findAndUpdatePermitById(string $permitId , array $data){
        $permit = Permit::find($permitId);

        if (!$permit) {
            return null;
        }

        $permit->update($data);
        return $permit;
    }
     public function findAndDeletePermitById(string $permitId){
         $permit = Permit::find($permitId);
        if (!$permit) {
            return null;
        }

      return [
         'permit'=>$permit,
         'deletePermit'=>$permit->delete()
        ];
    }

   public function getAllPermitsDashobard()
    {
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();

        // Dashboard counts
        $dashboard = [
            'totalPermits' => Permit::count(),
            'permitsToday' => Permit::whereDate('created_at', $today)->count(),
            'permitsThisWeek' => Permit::whereBetween('created_at', [$weekStart, now()])->count(),
            'permitsThisMonth' => Permit::whereBetween('created_at', [$monthStart, now()])->count(),
        ];

        // Group by year
     $permitsByYear = Permit::selectRaw("YEAR(STR_TO_DATE(issued_date, '%m/%d/%Y')) as year")
    ->selectRaw('COUNT(*) as total')
    ->selectRaw("SUM(CASE WHEN permit_type = 'Transport' THEN 1 ELSE 0 END) as Transport")
    ->selectRaw("SUM(CASE WHEN permit_type = 'Event' THEN 1 ELSE 0 END) as Event")
    ->selectRaw("SUM(CASE WHEN permit_type = 'Business' THEN 1 ELSE 0 END) as Business")
    ->selectRaw("SUM(CASE WHEN permit_type = 'Construction' THEN 1 ELSE 0 END) as Construction")
    ->groupBy('year')
    ->orderBy('year', 'asc')
    ->get();
        $dashboard['permitsByStatus'] = Permit::selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->orderBy('status', 'asc')
        ->get();
        $dashboard['latestPermits'] = Permit::select('permit_type', 'created_at', 'status','id','permit_no')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        $dashboard['permitsByYear'] = $permitsByYear;
        $dashboard['allPermit'] = Permit::where('status', '!=', 'Cancelled')
        ->orderBy('created_at', 'desc')
        ->get();
        return $dashboard;
    }

    public function getAllPermitsDashboardByUser($userId)
        {
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();
            $monthStart = now()->startOfMonth();


            $dashboard = [
                'totalPermits' => Permit::where('created_by', $userId)->count(),
                'permitsToday' => Permit::where('created_by', $userId)
                    ->whereDate('created_at', $today)
                    ->count(),
                'permitsThisWeek' => Permit::where('created_by', $userId)
                    ->whereBetween('created_at', [$weekStart, now()])
                    ->count(),
                'permitsThisMonth' => Permit::where('created_by', $userId)
                    ->whereBetween('created_at', [$monthStart, now()])
                    ->count(),
            ];


            $permitsByYear = Permit::where('created_by', $userId)
                ->selectRaw("YEAR(STR_TO_DATE(issued_date, '%m/%d/%Y')) as year")
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN permit_type = 'Transport' THEN 1 ELSE 0 END) as Transport")
                ->selectRaw("SUM(CASE WHEN permit_type = 'Event' THEN 1 ELSE 0 END) as Event")
                ->selectRaw("SUM(CASE WHEN permit_type = 'Business' THEN 1 ELSE 0 END) as Business")
                ->selectRaw("SUM(CASE WHEN permit_type = 'Construction' THEN 1 ELSE 0 END) as Construction")
                ->groupBy('year')
                ->orderBy('year', 'asc')
                ->get();

            $dashboard['permitsByStatus'] = Permit::where('created_by', $userId)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->orderBy('status', 'asc')
                ->get();
            $dashboard['permitByUserId'] = Permit::where('created_by', $userId)
                ->select('permit_type', 'created_at', 'status','id','permit_no','lat','lng')
                ->get();


            $dashboard['latestPermits'] = Permit::where('created_by', $userId)
                ->select('permit_type', 'created_at', 'status','id','permit_no')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $dashboard['permitsByYear'] = $permitsByYear;


            return $dashboard;
        }

         public function getPermitByUserId($userId)
        {
              return Permit::with('creator:id,name,email')->where('created_by', $userId)
                    ->orderBy('created_at', 'desc')
                    ->get();
        }


         public function findPermitById($pertmiId)
        {
            $permits = Permit::with('creator')->where('permit_no', $pertmiId)->first();

            return $permits;
        }

        public function getPermitBySteps(array $steps = [])
        {
            $query = Permit::with('creator');

            if (!empty($steps)) {
                $query->whereIn('steps', $steps);
            }

            return $query->get();
        }





}
