<?php

declare(strict_types=1);

namespace App\Utils;

use Exception;

class Mailer
{
    /**
     * Internal method to send email via Resend API (HTTPS Port 443)
     * This bypasses the SMTP port blocks on Railway.
     */
    private static function sendViaResend(string $to, string $subject, string $htmlBody): bool
    {
        $apiKey = getenv('RESEND_API_KEY');
        if (!$apiKey) {
            error_log("Mailer Error: RESEND_API_KEY is not set.");
            return false;
        }

        // FOR RESEND: You can ONLY use 'onboarding@resend.dev' until you verify your own domain.
        $fromEmail = 'onboarding@resend.dev';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'ASRV Kindergarten';

        $payload = [
            'from' => "$fromName <$fromEmail>",
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlBody,
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("Resend API Error (HTTP $httpCode): " . $response);
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
        return self::sendViaResend($parentEmail, $subject, $body);
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
        return self::sendViaResend($parentEmail, $subject, $body);
    }

    public static function sendPasswordReset(string $email, string $resetLink): bool
    {
        $subject = 'Password Reset Request';
        $body = "
            <p>Hello,</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='{$resetLink}'>Reset Password</a></p>
            <p>This link will expire in 30 minutes.</p>
            <p>If you did not request this, please ignore this email.</p>
        ";
        return self::sendViaResend($email, $subject, $body);
    }

    /**
     * Debugging method for testing Resend connectivity
     */
    public static function sendTestEmailWithDebug(string $targetEmail): bool
    {
        $success = self::sendViaResend($targetEmail, 'Resend API Test', '<h1>API Connection Successful!</h1><p>This email was sent via the Resend HTTPS API, bypassing Railway port blocks.</p>');
        if (!$success) {
            echo "<br/><strong>API Error:</strong> Check your RESEND_API_KEY and Ensure you are sending to a verified email address.<br/>";
        }
        return $success;
    }
}
