<?php

declare(strict_types=1);

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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
        return (string) (getenv('MAIL_FROM_EMAIL') ?: getenv('MAIL_FROM') ?: 'no-reply@example.com');
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

    private static function sendViaSmtp(string $to, string $subject, string $htmlBody): bool
    {
        $host = getenv('SMTP_HOST') ?: '';
        $port = (int) (getenv('SMTP_PORT') ?: 587);
        $user = getenv('SMTP_USER') ?: '';
        $pass = getenv('SMTP_PASS') ?: '';
        $secure = strtolower((string) (getenv('SMTP_SECURE') ?: 'tls'));

        if ($host === '' || $user === '' || $pass === '') {
            $msg = 'SMTP credentials are incomplete.';
            error_log('Mailer Error: ' . $msg);
            self::markFailure('smtp', $msg);
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->Port = $port;
            $mail->Timeout = 12;
            $mail->SMTPKeepAlive = false;

            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom(self::getSenderEmail(), self::getSenderName());
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            self::markSuccess('smtp');
            return true;
        } catch (PHPMailerException $e) {
            $msg = 'SMTP Error: ' . $e->getMessage();
            error_log($msg);
            self::markFailure('smtp', $msg);
            return false;
        }
    }

    private static function sendWithFallback(string $to, string $subject, string $htmlBody): bool
    {
        // Prefer Brevo first for this project.
        $providerOrder = strtolower((string) (getenv('MAIL_PROVIDER_ORDER') ?: 'brevo,resend,smtp'));
        $providers = array_filter(array_map('trim', explode(',', $providerOrder)));

        self::$lastProvider = '';
        self::$lastError = '';

        foreach ($providers as $provider) {
            $sent = false;
            if ($provider === 'brevo') {
                $sent = self::sendViaBrevo($to, $subject, $htmlBody);
            } elseif ($provider === 'resend') {
                $sent = self::sendViaResend($to, $subject, $htmlBody);
            } elseif ($provider === 'smtp') {
                $sent = self::sendViaSmtp($to, $subject, $htmlBody);
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

    public static function sendPasswordReset(string $email, string $resetLink, int $expiryMinutes = 120): bool
    {
        $subject = 'Password Reset Request';
        $expiryMinutes = max(30, $expiryMinutes);
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 18px; border: 1px solid #e5e7eb; border-radius: 8px; color:#111827;'>
                <h2 style='margin:0 0 12px;'>Password Reset</h2>
                <p style='margin:0 0 12px;'>We received a request to reset your ASRV account password.</p>
                <p style='margin:0 0 16px;'>Use this secure link:</p>
                <p style='word-break:break-all; margin:0 0 16px;'>
                    <a href='{$resetLink}'>{$resetLink}</a>
                </p>
                <p style='margin:0 0 12px;'>This link expires in {$expiryMinutes} minutes.</p>
                <p style='margin:0; font-size:12px; color:#6b7280;'>If you did not request this, ignore this email.</p>
            </div>
        ";
        return self::sendWithFallback($email, $subject, $body);
    }
}
