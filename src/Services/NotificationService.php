<?php

declare(strict_types=1);

namespace App\Services;

use App\Utils\Mailer;
use App\Models\Student;
use App\Models\User;
use Appwrite\Client;
use Appwrite\Services\Messaging;
use PDO;
use Exception;

class NotificationService
{
    private ?Messaging $messaging = null;

    public function __construct(private PDO $db)
    {
        $endpoint = getenv('APPWRITE_ENDPOINT');
        $projectId = getenv('APPWRITE_PROJECT');
        $apiKey = getenv('APPWRITE_KEY');

        if ($endpoint && $projectId && $apiKey) {
            $client = new Client();
            $client->setEndpoint($endpoint)
                   ->setProject($projectId)
                   ->setKey($apiKey);
            $messaging = new Messaging($client);
            $this->messaging = $messaging;
        }
    }

    /**
     * Send an absentee notification (Email + Push)
     */
    public function sendAbsenteeAlert(int $studentId, string $date): bool
    {
        try {
            // 1. Get Student and Parent Info
            $stmt = $this->db->prepare('
                SELECT s.name AS student_name, u.id AS parent_id, u.email AS parent_email, u.fcm_token
                FROM students s
                INNER JOIN users u ON u.id = s.parent_id
                WHERE s.id = :id
            ');
            $stmt->execute(['id' => $studentId]);
            $data = $stmt->fetch();

            if (!$data) {
                return false;
            }

            $studentName = $data['student_name'];
            $parentEmail = $data['parent_email'];
            $parentId = (int) $data['parent_id'];

            // 2. Send Email
            Mailer::sendAbsentEmail($parentEmail, $studentName, $date);

            // 3. Send Push Notification via Appwrite
            $title = "Student Absence Alert";
            $body = "Your child {$studentName} was marked absent today ({$date}).";
            $this->sendPush($parentId, $title, $body);

            // 4. Record to internal notifications table
            $this->logToDatabase($parentId, $title, $body, 'attendance');

            return true;
        } catch (Exception $e) {
            error_log("NotificationService Error (Absentee): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a fee reminder alert (Email + Push)
     */
    public function sendFeeAlert(int $studentId, string $amount, string $dueDate): bool
    {
        try {
            $stmt = $this->db->prepare('
                SELECT s.name AS student_name, u.id AS parent_id, u.email AS parent_email
                FROM students s
                INNER JOIN users u ON u.id = s.parent_id
                WHERE s.id = :id
            ');
            $stmt->execute(['id' => $studentId]);
            $data = $stmt->fetch();

            if (!$data) return false;

            $parentId = (int) $data['parent_id'];
            $parentEmail = $data['parent_email'];
            $studentName = $data['student_name'];

            // 1. Email
            Mailer::sendFeeReminder($parentEmail, $studentName, $amount, $dueDate);

            // 2. Push
            $title = "Fee Payment Reminder";
            $body = "Fee of {$amount} is pending for {$studentName}. Due date: {$dueDate}.";
            $this->sendPush($parentId, $title, $body);

            // 3. DB Log
            $this->logToDatabase($parentId, $title, $body, 'fee');

            return true;
        } catch (Exception $e) {
            error_log("NotificationService Error (Fee): " . $e->getMessage());
            return false;
        }
    }

    private function sendPush(int $userId, string $title, string $body): void
    {
        if (!$this->messaging) {
            return;
        }

        try {
            // In Appwrite, we typically target users by their ID or Label
            // Assuming Appwrite User ID matches our DB User ID or we have a mapping
            $this->messaging->createMessage(
                messageId: 'unique()',
                title: $title,
                content: $body,
                topics: [],
                users: [(string) $userId],
                targets: []
            );
        } catch (Exception $e) {
            error_log("Appwrite Push Error: " . $e->getMessage());
        }
    }

    private function logToDatabase(int $userId, string $title, string $body, string $type): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO notifications (user_id, title, body, type)
            VALUES (:user_id, :title, :body, :type)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type
        ]);
    }
}
