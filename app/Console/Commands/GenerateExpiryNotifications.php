<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permit;
use App\Models\AppNotification;
use App\Providers\PHPMailerService;
use Carbon\Carbon;

class GenerateExpiryNotifications extends Command
{
    protected $signature = 'notifications:expiry';
    protected $description = 'Generate in-app + email notifications for permits expiring within 30, 7, or 1 day';

    public function handle(PHPMailerService $mailer): int
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

                if (!empty($permit->creator->email)) {
                    $whenLabel = $diff === 1 ? 'tomorrow' : "in {$diff} days";
                    $subject   = "Reminder: Permit {$permit->permit_no} expires {$whenLabel}";
                    $body = "
                        <p>Dear <strong>{$permit->creator->name}</strong>,</p>
                        <p>
                            This is a friendly reminder that your permit
                            <strong>{$permit->permit_no}</strong> will expire on
                            <strong>{$permit->expiry_date}</strong> ({$whenLabel}).
                        </p>
                        <p>
                            To avoid any disruption, please file a renewal application before the
                            expiration date through your account or by visiting the DENR-CENRO office.
                        </p>
                        <br>
                        <p>Thank you,</p>
                        <p>
                            <strong>
                                Department of Environment and Natural Resources (DENR)<br>
                                Community Environment and Natural Resources Office (CENRO)<br>
                                Brgy. Duhat, Santa Cruz, Laguna
                            </strong>
                        </p>
                    ";
                    $mailer->send($permit->creator->email, $subject, $body);
                }

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

                if (!empty($permit->creator->email)) {
                    $subject = "Notice: Permit {$permit->permit_no} has expired";
                    $body = "
                        <p>Dear <strong>{$permit->creator->name}</strong>,</p>
                        <p>
                            We wish to inform you that your permit
                            <strong>{$permit->permit_no}</strong> has <strong>expired</strong>
                            as of <strong>{$permit->expiry_date}</strong>.
                        </p>
                        <p>
                            This permit is no longer valid for use. To continue your operations,
                            please apply for a renewal through your account or visit the
                            DENR-CENRO office.
                        </p>
                        <br>
                        <p>Thank you,</p>
                        <p>
                            <strong>
                                Department of Environment and Natural Resources (DENR)<br>
                                Community Environment and Natural Resources Office (CENRO)<br>
                                Brgy. Duhat, Santa Cruz, Laguna
                            </strong>
                        </p>
                    ";
                    $mailer->send($permit->creator->email, $subject, $body);
                }

                $created++;
            }
        }

        $this->info("Generated {$created} notification(s).");
        return Command::SUCCESS;
    }
}
