<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use App\Models\Permit;
use App\Models\User;

class ArchiveController extends Controller
{
    private function ensureAdmin()
    {
        $user = auth()->user();
        $role = $user['role'] ?? null;
        if ($role !== 'admin') {
            abort(response()->json(['message' => 'Only admins may access the archive.'], 403));
        }
    }

    public function permits()
    {
        $this->ensureAdmin();
        try {
            $data = Permit::onlyTrashed()
                ->with('creator:id,name,email')
                ->orderByDesc('deleted_at')
                ->get();
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function users()
    {
        $this->ensureAdmin();
        try {
            $data = User::onlyTrashed()
                ->orderByDesc('deleted_at')
                ->get();
            return response()->json([
                'message' => 'Retrieve successfully!',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function restorePermit(string $id)
    {
        $this->ensureAdmin();
        try {
            $permit = Permit::onlyTrashed()->find($id);
            if (!$permit) {
                return response()->json(['message' => 'Permit not found in archive'], 404);
            }
            $permit->restore();
            $permit->update(['archived_by' => null, 'archive_reason' => null]);
            return response()->json([
                'message' => 'Permit restored successfully!',
                'data' => $permit,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function restoreUser(string $id)
    {
        $this->ensureAdmin();
        try {
            $user = User::onlyTrashed()->find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found in archive'], 404);
            }
            $user->restore();
            $user->update(['archived_by' => null, 'archive_reason' => null]);
            return response()->json([
                'message' => 'User restored successfully!',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function purgePermit(string $id)
    {
        $this->ensureAdmin();
        try {
            $permit = Permit::onlyTrashed()->find($id);
            if (!$permit) {
                return response()->json(['message' => 'Permit not found in archive'], 404);
            }
            // Now safe to clean up the QR file — record is leaving for good
            $qrPath = public_path('storage/qrcodes/' . $permit->qrcode);
            if ($permit->qrcode && File::exists($qrPath)) {
                File::delete($qrPath);
            }
            $permit->forceDelete();
            return response()->json(['message' => 'Permit permanently deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function purgeUser(string $id)
    {
        $this->ensureAdmin();
        try {
            $user = User::onlyTrashed()->find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found in archive'], 404);
            }
            $user->forceDelete();
            return response()->json(['message' => 'User permanently deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
