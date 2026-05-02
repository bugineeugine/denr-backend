<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    /**
     * Roles that staff/admin notifications are sent to.
     * Anyone whose `role` is in this list (or has a non-null `position`) is treated as staff.
     */
    public static function staffRoles(): array
    {
        return ['admin', 'officer', 'validator'];
    }

    /**
     * Send to a single specific user.
     */
    public static function notifyUser(string $userId, array $data): void
    {
        AppNotification::create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * Send to all admin/officer/validator/staff-position users.
     */
    public static function notifyStaff(array $data): void
    {
        $staff = User::where(function ($q) {
            $q->whereIn('role', self::staffRoles())
              ->orWhereNotNull('position');
        })->pluck('id');

        foreach ($staff as $uid) {
            self::notifyUser($uid, $data);
        }
    }

    /**
     * Send to a position holder (e.g. only "Clerk", only "Chief").
     */
    public static function notifyByPosition(string $position, array $data): void
    {
        $ids = User::where('position', $position)->pluck('id');
        foreach ($ids as $uid) {
            self::notifyUser($uid, $data);
        }
    }
}
