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
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER');
        $mail->Password   = getenv('SMTP_PASS');
        $mail->SMTPSecure = getenv('SMTP_SECURE') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int) (getenv('SMTP_PORT') ?: 587);

        $fromEmail = getenv('MAIL_FROM') ?: 'no-reply@yourdomain.com';
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Kindergarten School';

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
}
