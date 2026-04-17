<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Grade
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO grades (student_id, subject_id, exam_name, marks, grade)
            VALUES (:student_id, :subject_id, :exam_name, :marks, :grade)
        ');
        $stmt->execute([
            'student_id' => $data['student_id'],
            'subject_id' => $data['subject_id'] ?? null,
            'exam_name' => $data['exam_name'],
            'marks' => $data['marks'],
            'grade' => $data['grade'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byStudent(int $studentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM grades WHERE student_id = :student_id ORDER BY id DESC');
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query('
            SELECT g.*, s.name AS student_name, sub.name AS subject_name
            FROM grades g
            INNER JOIN students s ON s.id = g.student_id
            LEFT JOIN subjects sub ON sub.id = g.subject_id
            ORDER BY g.id DESC
        ');
        return $stmt->fetchAll();
    }
}
