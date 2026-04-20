<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppNotification;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $notifications = AppNotification::where('user_id', $user['id'])
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            $unreadCount = AppNotification::where('user_id', $user['id'])
                ->whereNull('read_at')
                ->count();

            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => [
                    'notifications' => $notifications,
                    'unreadCount' => $unreadCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markRead(string $id)
    {
        try {
            $user = auth()->user();
            $note = AppNotification::where('user_id', $user['id'])->find($id);
            if (!$note) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $note->update(['read_at' => now()]);
            return response()->json(['message' => 'Marked as read', 'data' => $note], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAllRead()
    {
        try {
            $user = auth()->user();
            AppNotification::where('user_id', $user['id'])
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
            return response()->json(['message' => 'All marked as read'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = auth()->user();
            $note = AppNotification::where('user_id', $user['id'])->find($id);
            if (!$note) {
                return response()->json(['message' => 'Not found'], 404);
            }
            $note->delete();
            return response()->json(['message' => 'Deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
