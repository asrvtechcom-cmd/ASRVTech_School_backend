<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Attendance
{
    public function __construct(private PDO $db)
    {
    }

    public function mark(int $studentId, string $date, string $status): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO attendance (student_id, date, status)
            VALUES (:student_id, :date, :status)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ');
        return $stmt->execute([
            'student_id' => $studentId,
            'date' => $date,
            'status' => $status,
        ]);
    }

    public function byStudent(int $studentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM attendance WHERE student_id = :student_id ORDER BY date DESC');
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }
}
