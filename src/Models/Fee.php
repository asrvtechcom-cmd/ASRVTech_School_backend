<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Fee
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO fees (student_id, amount, due_date, status, paid_at)
            VALUES (:student_id, :amount, :due_date, :status, :paid_at)
        ');
        $stmt->execute([
            'student_id' => $data['student_id'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'paid_at' => $data['paid_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byStudent(int $studentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM fees WHERE student_id = :student_id ORDER BY id DESC');
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query('
            SELECT f.*, s.name AS student_name
            FROM fees f
            INNER JOIN students s ON s.id = f.student_id
            ORDER BY f.id DESC
        ');
        return $stmt->fetchAll();
    }

    public function pay(int $id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE fees SET status = "paid", paid_at = :paid_at WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'paid_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function markOverdue(): int
    {
        $stmt = $this->db->prepare('
            UPDATE fees SET status = "overdue" 
            WHERE status = "pending" AND due_date < :today
        ');
        $stmt->execute(['today' => date('Y-m-d')]);
        return $stmt->rowCount();
    }
}
