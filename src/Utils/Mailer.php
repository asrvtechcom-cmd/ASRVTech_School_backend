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
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 10px;'>
                <h2 style='color: #333;'>Password Reset Request</h2>
                <p style='color: #555; font-size: 16px;'>Hello,</p>
                <p style='color: #555; font-size: 16px;'>We received a request to reset your password. Click the button below to set a new one:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' style='background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 16px 32px; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 18px; display: inline-block; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);'>Reset Password Now</a>
                </div>

                <p style='color: #777; font-size: 14px;'>This link will expire in 30 minutes for your security.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #999; font-size: 12px; text-align: center;'>If you did not request this, please ignore this email.</p>
            </div>
        ";
        return self::sendViaResend($email, $subject, $body);
    }

    /**
     * Debugging method for testing Resend connectivity
     */
    public static function sendTestEmailWithDebug(string $targetEmail): bool
    {
        $payload = [
            'from' => "ASRV Kindergarten <onboarding@resend.dev>",
            'to' => [$targetEmail],
            'subject' => 'Resend API Debug Test',
            'html' => '<h1>API Connection Successful!</h1>',
        ];

        $apiKey = getenv('RESEND_API_KEY');
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

        echo "<div style='background: #fee; padding: 10px; border: 1px solid red; margin-top: 10px;'>";
        echo "<strong>RAW API RESPONSE:</strong><br/>";
        echo "HTTP Code: $httpCode<br/>";
        echo "Response: <pre>" . htmlspecialchars((string)$response) . "</pre>";
        echo "</div>";
        
        return false;
    }
}
