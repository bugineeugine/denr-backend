<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\PermitRepositoryInterface;
class DashboardController extends Controller
{
    protected $permits;
    public function __construct(PermitRepositoryInterface $permits){
        $this->permits = $permits;
    }

    public function index()
    {
        try {
            $stats = $this->permits->getAllPermitsDashobard();

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
