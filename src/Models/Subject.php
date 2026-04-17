<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Subject
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO subjects (name, class_id, teacher_id)
            VALUES (:name, :class_id, :teacher_id)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'class_id' => $data['class_id'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(?int $classId = null): array
    {
        $sql = '
            SELECT s.*, c.name as class_name, t.name as teacher_name 
            FROM subjects s 
            LEFT JOIN classes c ON s.class_id = c.id 
            LEFT JOIN teachers t ON s.teacher_id = t.id
        ';
        
        if ($classId !== null) {
            $sql .= ' WHERE s.class_id = :class_id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['class_id' => $classId]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY s.name ASC');
        }
        
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE subjects 
            SET name = :name, class_id = :class_id, teacher_id = :teacher_id 
            WHERE id = :id
        ');
        return $stmt->execute([
            'name' => $data['name'],
            'class_id' => $data['class_id'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM subjects WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
