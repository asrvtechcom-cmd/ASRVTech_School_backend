<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Message
{
    public function __construct(private PDO $db)
    {
    }

    public function send(int $senderId, int $receiverId, string $message): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO messages (sender_id, receiver_id, message)
            VALUES (:sender_id, :receiver_id, :message)
        ');
        $stmt->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function conversation(int $userA, int $userB): array
    {
        $stmt = $this->db->prepare('
            SELECT *
            FROM messages
            WHERE (sender_id = :userA AND receiver_id = :userB)
               OR (sender_id = :userB AND receiver_id = :userA)
            ORDER BY created_at ASC
        ');
        $stmt->execute([
            'userA' => $userA,
            'userB' => $userB,
        ]);
        return $stmt->fetchAll();
    }

    public function markAsRead(int $receiverId, int $senderId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE messages
            SET is_read = 1
            WHERE receiver_id = :receiver_id AND sender_id = :sender_id
        ');
        return $stmt->execute([
            'receiver_id' => $receiverId,
            'sender_id' => $senderId,
        ]);
    }

    public function inbox(int $userId): array
    {
        // Get the last message for each conversation
        $stmt = $this->db->prepare('
            SELECT 
                u.id AS other_user_id, 
                u.name AS other_user_name,
                u.role AS other_user_role,
                m.message AS last_message,
                m.created_at AS last_message_time,
                m.is_read,
                m.sender_id
            FROM messages m
            INNER JOIN (
                SELECT 
                    MAX(id) as max_id
                FROM messages
                WHERE sender_id = :userId OR receiver_id = :userId
                GROUP BY 
                    CASE 
                        WHEN sender_id = :userId THEN receiver_id 
                        ELSE sender_id 
                    END
            ) last_msgs ON m.id = last_msgs.max_id
            INNER JOIN users u ON u.id = (
                CASE 
                    WHEN m.sender_id = :userId THEN m.receiver_id 
                    ELSE m.sender_id 
                END
            )
            ORDER BY m.created_at DESC
        ');
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetchAll();
    }
}
