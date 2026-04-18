<?php

declare(strict_types=1);

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RuntimeException;

class Mailer
{
    private static function getMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // Server settings from ENV
        $mail->isSMTP();
        $host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $port = (int) (getenv('SMTP_PORT') ?: 587);
        $user = getenv('SMTP_USER');
        $pass = getenv('SMTP_PASS');
        $secure = getenv('SMTP_SECURE');

        // Automatic security selection if not explicitly provided
        if (!$secure) {
            $secure = ($port === 465) ? 'ssl' : 'tls';
        }

        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = ($secure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;

        $fromEmail = getenv('MAIL_FROM') ?: $user ?: 'no-reply@asrvtech.com';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'ASRV Kindergarten';

        $mail->setFrom($fromEmail, $fromName);
        $mail->isHTML(true);

        return $mail;
    }

    public static function sendAbsentEmail(string $parentEmail, string $studentName, string $date): bool
    {
        try {
            $mail = self::getMailer();
            $mail->addAddress($parentEmail);
            $mail->Subject = 'Student Absence Notification';
            
            $mail->Body = "
                <p>Dear Parent,</p>
                <p>Your child <strong>{$studentName}</strong> was marked <strong>ABSENT</strong> today.</p>
                <p>Date: {$date}</p>
                <p>Please contact the school if needed.</p>
                <p>Regards,<br>School Administration</p>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (Absent): " . $e->getMessage());
            return false;
        }
    }

    public static function sendFeeReminder(string $parentEmail, string $studentName, string $amount, string $dueDate): bool
    {
        try {
            $mail = self::getMailer();
            $mail->addAddress($parentEmail);
            $mail->Subject = 'Fee Payment Reminder';
            
            $mail->Body = "
                <p>Dear Parent,</p>
                <p>This is a reminder that the school fee for your child <strong>{$studentName}</strong> is pending.</p>
                <p>Amount: {$amount}</p>
                <p>Due Date: {$dueDate}</p>
                <p>Please make the payment as soon as possible.</p>
                <p>Thank you,<br>School Administration</p>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (Fee): " . $e->getMessage());
            return false;
        }
    }

    public static function sendPasswordReset(string $email, string $resetLink): bool
    {
        try {
            $mail = self::getMailer();
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request';
            
            $mail->Body = "
                <p>Hello,</p>
                <p>Click the link below to reset your password:</p>
                <p><a href='{$resetLink}'>Reset Password</a></p>
                <p>This link will expire in 30 minutes.</p>
                <p>If you did not request this, please ignore this email.</p>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error (Reset): " . $e->getMessage());
            return false;
        }
    }

    public static function sendTestEmailWithDebug(string $targetEmail): bool
    {
        try {
            $mail = self::getMailer();
            $mail->SMTPDebug = 2; // Output detailed server-to-client exchange
            $mail->Debugoutput = 'echo';
            
            $mail->addAddress($targetEmail);
            $mail->Subject = 'SMTP Debug Test';
            $mail->Body = '<h1>Testing SMTP</h1><p>If you see this, authentication worked!</p>';

            return $mail->send();
        } catch (Exception $e) {
            echo "<br/><strong>Detailed Error:</strong> " . $mail->ErrorInfo . "<br/>";
            return false;
        }
    }
}
