<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    private function ensureAdmin()
    {
        $user = auth()->user();
        if (($user['role'] ?? null) !== 'admin') {
            abort(response()->json(['message' => 'Only admins may access activity logs.'], 403));
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();
        try {
            $query = ActivityLog::query()->orderByDesc('created_at');

            if ($request->filled('action')) {
                $query->where('action', $request->query('action'));
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->query('user_id'));
            }
            if ($request->filled('subject_type')) {
                $query->where('subject_type', 'like', '%' . $request->query('subject_type'));
            }
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->query('from'));
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->query('to'));
            }

            $limit = min((int) $request->query('limit', 200), 500);
            $logs = $query->limit($limit)->get();

            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $logs,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
