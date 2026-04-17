<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class LeaveRequest
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status)
            VALUES (:user_id, :leave_type, :start_date, :end_date, :reason, \'pending\')
        ');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'leave_type' => $data['leave_type'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(?int $userId = null): array
    {
        $sql = '
            SELECT lr.*, u.name as user_name, u.role as user_role 
            FROM leave_requests lr 
            LEFT JOIN users u ON lr.user_id = u.id
        ';
        
        if ($userId !== null) {
            $sql .= ' WHERE lr.user_id = :user_id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY lr.created_at DESC');
        }
        
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE leave_requests SET status = :status WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'id' => $id,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM leave_requests WHERE id = :id AND user_id = :user_id AND status = \'pending\'');
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
