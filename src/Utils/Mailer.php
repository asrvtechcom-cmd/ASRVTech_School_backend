<?php

declare(strict_types=1);

namespace App\Utils;

class Mailer
{
    private static string $lastProvider = '';
    private static string $lastError = '';

    public static function getLastProvider(): string
    {
        return self::$lastProvider;
    }

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    private static function markSuccess(string $provider): void
    {
        self::$lastProvider = $provider;
        self::$lastError = '';
    }

    private static function markFailure(string $provider, string $error): void
    {
        self::$lastProvider = $provider;
        self::$lastError = $error;
    }

    private static function getSenderEmail(): string
    {
        return (string) (
            getenv('BREVO_SENDER_EMAIL')
            ?: getenv('MAIL_FROM_EMAIL')
            ?: getenv('MAIL_FROM')
            ?: getenv('SMTP_USER')
            ?: 'no-reply@example.com'
        );
    }

    private static function getSenderName(): string
    {
        return (string) (getenv('MAIL_FROM_NAME') ?: 'ASRV Kindergarten');
    }

    private static function getBrevoSenderEmail(): string
    {
        return (string) (getenv('BREVO_SENDER_EMAIL') ?: self::getSenderEmail());
    }

    private static function getBrevoSenderName(): string
    {
        return (string) (getenv('BREVO_SENDER_NAME') ?: self::getSenderName());
    }

    /**
     * Internal method to send email via Brevo API (HTTPS Port 443)
     * This bypasses both Railway's SMTP blocks AND Resend's Sandbox limits.
     */
    private static function sendViaBrevo(string $to, string $subject, string $htmlBody): bool
    {
        $apiKey = getenv('BREVO_API_KEY');
        if (!$apiKey) {
            $msg = "BREVO_API_KEY is not set.";
            error_log("Mailer Error: " . $msg);
            self::markFailure('brevo', $msg);
            return false;
        }

        $fromEmail = self::getBrevoSenderEmail();
        $fromName = self::getBrevoSenderName();

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || str_ends_with(strtolower($fromEmail), '@example.com')) {
            $msg = "Invalid Brevo sender email configured: {$fromEmail}";
            error_log("Mailer Error: " . $msg);
            self::markFailure('brevo', $msg);
            return false;
        }


        $payload = [
            'sender' => [
                'name' => $fromName,
                'email' => $fromEmail
            ],
            'to' => [
                ['email' => $to]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlBody,
            'textContent' => trim(preg_replace('/\s+/', ' ', strip_tags($htmlBody))),
            'replyTo' => ['email' => (string) (getenv('MAIL_REPLY_TO') ?: $fromEmail), 'name' => $fromName],
            'headers' => ['X-Mailer' => 'ASRVTech API'],
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'api-key: ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // Retry once with relaxed SSL only when local CA bundle is missing.
        if ($response === false && stripos($curlError, 'unable to get local issuer certificate') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
        }

        // Retry once for transient server/network failures.
        if (($response === false && $curlError !== '') || $httpCode >= 500) {
            usleep(250000);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
        }
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            self::markSuccess('brevo');
            return true;
        }

        if ($response === false) {
            $msg = "Brevo API cURL Error: " . $curlError;
            error_log($msg);
            self::markFailure('brevo', $msg);
            return false;
        }

        $msg = "Brevo API Error (HTTP $httpCode): " . $response;
        error_log($msg);
        self::markFailure('brevo', $msg);
        return false;
    }

    private static function sendViaResend(string $to, string $subject, string $htmlBody): bool
    {
        $apiKey = getenv('RESEND_API_KEY');
        if (!$apiKey) {
            $msg = 'RESEND_API_KEY is not set.';
            error_log('Mailer Error: ' . $msg);
            self::markFailure('resend', $msg);
            return false;
        }

        $from = getenv('RESEND_FROM') ?: (self::getSenderName() . ' <' . self::getSenderEmail() . '>');
        $payload = [
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlBody,
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // Retry once with relaxed SSL only when local CA bundle is missing.
        if ($response === false && stripos($curlError, 'unable to get local issuer certificate') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
        }
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            self::markSuccess('resend');
            return true;
        }

        if ($response === false) {
            $msg = "Resend API cURL Error: " . $curlError;
            error_log($msg);
            self::markFailure('resend', $msg);
            return false;
        }

        $msg = "Resend API Error (HTTP $httpCode): " . $response;
        error_log($msg);
        self::markFailure('resend', $msg);
        return false;
    }

    private static function sendWithFallback(string $to, string $subject, string $htmlBody): bool
    {
        // PHPMailer/SMTP removed. Keep API providers only for faster failover.
        $providerOrder = strtolower((string) (getenv('MAIL_PROVIDER_ORDER') ?: 'brevo,resend'));
        $providers = array_filter(array_map('trim', explode(',', $providerOrder)));

        self::$lastProvider = '';
        self::$lastError = '';

        foreach ($providers as $provider) {
            $sent = false;
            if ($provider === 'brevo') {
                $sent = self::sendViaBrevo($to, $subject, $htmlBody);
            } elseif ($provider === 'resend') {
                $sent = self::sendViaResend($to, $subject, $htmlBody);
            }

            if ($sent) {
                return true;
            }
        }

        if (self::$lastError === '') {
            self::markFailure('none', 'No configured mail provider succeeded.');
        }
        return false;
    }

    public static function sendAbsentEmail(string $parentEmail, string $studentName, string $date): bool
    {
        $subject = 'Student Absence Notification';
        $body = "
            <p>Dear Parent,</p>
            <p>Your child <strong>{$studentName}</strong> was marked <strong>ABSENT</strong> today.</p>
            <p>Date: {$date}</p>
            <p>Please contact the school if needed.</p>
            <p>Regards,<br>School Administration</p>
        ";
        return self::sendWithFallback($parentEmail, $subject, $body);
    }

    public static function sendFeeReminder(string $parentEmail, string $studentName, string $amount, string $dueDate): bool
    {
        $subject = 'Fee Payment Reminder';
        $body = "
            <p>Dear Parent,</p>
            <p>This is a reminder that the school fee for your child <strong>{$studentName}</strong> is pending.</p>
            <p>Amount: {$amount}</p>
            <p>Due Date: {$dueDate}</p>
            <p>Please make the payment as soon as possible.</p>
            <p>Thank you,<br>School Administration</p>
        ";
        return self::sendWithFallback($parentEmail, $subject, $body);
    }

    public static function sendPasswordReset(string $email, string $resetLink, int $expiryMinutes = 10): bool
    {
        $subject = 'Password Reset Request';
        $expiryMinutes = max(1, $expiryMinutes);
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 18px; border: 1px solid #e5e7eb; border-radius: 8px; color:#111827;'>
                <h2 style='margin:0 0 12px;'>Password Reset</h2>
                <p style='margin:0 0 12px;'>We received a request to reset your ASRV account password.</p>
                <p style='margin:0 0 16px;'>Click the button below to continue:</p>
                <p style='margin:0 0 18px; text-align:center;'>
                    <a href='{$resetLink}' style='display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:6px;font-weight:700;'>Reset Password</a>
                </p>
                <p style='word-break:break-all; margin:0 0 16px; font-size:12px; color:#6b7280;'>
                    If button does not work, use this link: <a href='{$resetLink}'>{$resetLink}</a>
                </p>
                <p style='margin:0 0 12px;'>This link expires in {$expiryMinutes} minutes.</p>
                <p style='margin:0; font-size:12px; color:#6b7280;'>If you did not request this, ignore this email.</p>
            </div>
        ";
        return self::sendWithFallback($email, $subject, $body);
    }
}
