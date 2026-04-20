<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permit;
use App\Models\AppNotification;
use Carbon\Carbon;

class GenerateExpiryNotifications extends Command
{
    protected $signature = 'notifications:expiry';
    protected $description = 'Generate in-app notifications for permits expiring within 30, 7, or 1 day';

    public function handle(): int
    {
        $today = Carbon::today();
        $checkDays = [30, 7, 1];
        $created = 0;

        $permits = Permit::with('creator')
            ->where('status', 'Approved')
            ->get();

        foreach ($permits as $permit) {
            try {
                $expiry = Carbon::createFromFormat('m/d/Y', $permit->expiry_date);
            } catch (\Exception $e) {
                continue;
            }

            if (!$permit->creator) {
                continue;
            }

            $diff = $today->diffInDays($expiry, false);

            if (in_array($diff, $checkDays)) {
                $type = 'permit.expiring';
                $exists = AppNotification::where('user_id', $permit->created_by)
                    ->where('type', $type)
                    ->where('title', 'like', "%{$permit->permit_no}%")
                    ->whereDate('created_at', $today)
                    ->exists();

                if ($exists) {
                    continue;
                }

                AppNotification::create([
                    'user_id' => $permit->created_by,
                    'type' => $type,
                    'title' => "Permit {$permit->permit_no} expiring in {$diff} day(s)",
                    'message' => "Your permit {$permit->permit_no} will expire on {$permit->expiry_date}. Please renew before the expiration date.",
                    'link' => '/permits',
                    'severity' => $diff <= 1 ? 'critical' : ($diff <= 7 ? 'warning' : 'info'),
                ]);
                $created++;
            }

            if ($diff < 0 && $permit->status !== 'Expired') {
                $permit->update(['status' => 'Expired']);
                AppNotification::create([
                    'user_id' => $permit->created_by,
                    'type' => 'permit.expired',
                    'title' => "Permit {$permit->permit_no} has expired",
                    'message' => "Your permit {$permit->permit_no} expired on {$permit->expiry_date}. Please apply for renewal.",
                    'link' => '/permits',
                    'severity' => 'critical',
                ]);
                $created++;
            }
        }

        $this->info("Generated {$created} notification(s).");
        return Command::SUCCESS;
    }
}
