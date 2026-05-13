<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(string $action, Model $subject, ?array $changes = null, ?string $label = null): void
    {
        try {
            $user = auth()->user();
            ActivityLog::create([
                'user_id'       => $user['id'] ?? null,
                'user_name'     => $user['name'] ?? 'system',
                'user_role'     => $user['role'] ?? null,
                'action'        => $action,
                'subject_type'  => get_class($subject),
                'subject_id'    => (string) $subject->getKey(),
                'subject_label' => $label ?? self::defaultLabel($subject),
                'changes'       => $changes,
                'ip_address'    => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Never let logging crash the request
            logger()->warning('ActivityLogger failed: ' . $e->getMessage());
        }
    }

    public static function logRaw(string $action, string $subjectType, ?string $subjectId = null, ?string $label = null, ?array $changes = null): void
    {
        try {
            $user = auth()->user();
            ActivityLog::create([
                'user_id'       => $user['id'] ?? null,
                'user_name'     => $user['name'] ?? 'system',
                'user_role'     => $user['role'] ?? null,
                'action'        => $action,
                'subject_type'  => $subjectType,
                'subject_id'    => $subjectId,
                'subject_label' => $label,
                'changes'       => $changes,
                'ip_address'    => request()->ip(),
            ]);
        } catch (\Exception $e) {
            logger()->warning('ActivityLogger failed: ' . $e->getMessage());
        }
    }

    private static function defaultLabel(Model $subject): ?string
    {
        foreach (['permit_no', 'name', 'email', 'title', 'violator_name'] as $key) {
            if (!empty($subject->{$key})) {
                return (string) $subject->{$key};
            }
        }
        return null;
    }
}
