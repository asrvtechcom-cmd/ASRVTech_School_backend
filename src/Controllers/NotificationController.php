<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;

class NotificationController
{
    public function __construct(private PDO $db)
    {
    }

    public function send(): void
    {
        $user = AuthMiddleware::requireRole(['admin', 'teacher']);
        $input = Helper::getJsonInput();

        $receiverId = (int) ($input['user_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $body = trim($input['body'] ?? '');

        if ($receiverId <= 0 || $title === '' || $body === '') {
            Response::json(false, 'user_id, title and body are required', null, 422);
        }

        $stmt = $this->db->prepare('
            INSERT INTO notifications (user_id, title, body, sent_by)
            VALUES (:user_id, :title, :body, :sent_by)
        ');
        $stmt->execute([
            'user_id' => $receiverId,
            'title' => $title,
            'body' => $body,
            'sent_by' => (int) $user['user_id'],
        ]);

        Response::json(true, 'Notification sent', ['id' => (int) $this->db->lastInsertId()], 201);
    }

    public function user(): void
    {
        $auth = AuthMiddleware::authenticate();
        $userId = (int) ($_GET['user_id'] ?? $auth['user_id']);
        if ((int) $auth['user_id'] !== $userId && ($auth['role'] ?? '') !== 'admin') {
            Response::json(false, 'Forbidden to read this user notifications', null, 403);
        }

        $stmt = $this->db->prepare('
            SELECT id, user_id, title, body, sent_by, is_read, created_at
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY id DESC
        ');
        $stmt->execute(['user_id' => $userId]);

        Response::json(true, 'Notifications fetched', $stmt->fetchAll());
    }
}
