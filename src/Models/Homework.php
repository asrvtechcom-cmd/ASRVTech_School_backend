<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Homework
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO homework (class_id, subject_id, teacher_id, title, description, due_date, file_path)
            VALUES (:class_id, :subject_id, :teacher_id, :title, :description, :due_date, :file_path)
        ');
        $stmt->execute([
            'class_id' => $data['class_id'],
            'subject_id' => $data['subject_id'] ?? null,
            'teacher_id' => $data['teacher_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'file_path' => $data['file_path'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(?int $classId = null): array
    {
        $sql = '
            SELECT h.*, c.name as class_name, s.name as subject_name, t.name as teacher_name 
            FROM homework h 
            LEFT JOIN classes c ON h.class_id = c.id 
            LEFT JOIN subjects s ON h.subject_id = s.id 
            LEFT JOIN teachers t ON h.teacher_id = t.id
        ';
        
        if ($classId) {
            $sql .= ' WHERE h.class_id = :class_id';
            $stmt = $this->db->prepare($sql . ' ORDER BY h.id DESC');
            $stmt->execute(['class_id' => $classId]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY h.id DESC');
        }
        
        return $stmt->fetchAll();
    }
}
