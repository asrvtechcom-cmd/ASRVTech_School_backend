<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Teacher
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO teachers (name, email, phone, subject, photo, user_id)
            VALUES (:name, :email, :phone, :subject, :photo, :user_id)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'photo' => $data['photo'] ?? null,
            'user_id' => $data['user_id'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(): array
    {
        $stmt = $this->db->query('SELECT * FROM teachers ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE teachers
            SET name = :name, email = :email, phone = :phone, subject = :subject, photo = :photo, user_id = :user_id
            WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'photo' => $data['photo'] ?? null,
            'user_id' => $data['user_id'] ?? null,
        ]);
    }


    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM teachers WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
