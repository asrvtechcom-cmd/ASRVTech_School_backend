<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class SchoolClass
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO classes (name, section, teacher_id)
            VALUES (:name, :section, :teacher_id)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'section' => $data['section'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(): array
    {
        $stmt = $this->db->query('
            SELECT c.*, t.name as teacher_name 
            FROM classes c 
            LEFT JOIN teachers t ON c.teacher_id = t.id 
            ORDER BY c.name ASC
        ');
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE classes 
            SET name = :name, section = :section, teacher_id = :teacher_id 
            WHERE id = :id
        ');
        return $stmt->execute([
            'name' => $data['name'],
            'section' => $data['section'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
            'id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM classes WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM classes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
