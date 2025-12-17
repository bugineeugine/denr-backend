<?php

namespace App\Providers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailerService
{
    public function send(
        string $to,
        string $subject,
        string $body,
        bool $isHtml = true
    ): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = config('mail.mailers.smtp.host');
            $mail->SMTPAuth   = true;
            $mail->Username   = config('mail.mailers.smtp.username');
            $mail->Password   = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port       = config('mail.mailers.smtp.port');

            $mail->setFrom(
                config('mail.from.address'),
                config('mail.from.name')
            );

            $mail->addAddress($to);

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            logger()->error('PHPMailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
