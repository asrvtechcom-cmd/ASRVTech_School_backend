<?php

declare(strict_types=1);

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;

class Mailer
{
    /**
     * Internal method to send email via SMTP (like Gmail)
     * This bypasses Resend API limits and can send to any address.
     */
    private static function sendViaSMTP(string $to, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER'); // Your Gmail Address
            $mail->Password   = getenv('SMTP_PASS'); // Your Google App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) (getenv('SMTP_PORT') ?: 587);

            $fromEmail = getenv('SMTP_USER') ?: 'hello@asrv.com';
            $fromName  = getenv('MAIL_FROM_NAME') ?: 'ASRV Kindergarten';

            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;

        } catch (PHPMailerException $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        } catch (Exception $e) {
            error_log("General Mail Error: " . $e->getMessage());
            return false;
        }
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
        return self::sendViaSMTP($parentEmail, $subject, $body);
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
        return self::sendViaSMTP($parentEmail, $subject, $body);
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
        return self::sendViaSMTP($email, $subject, $body);
    }
}
