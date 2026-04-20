<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Teacher
{
    public function __construct(private PDO $db)
    {
        $this->ensureUserIdColumnExists();
    }

    private function ensureUserIdColumnExists(): void
    {
        try {
            // Check if user_id column exists
            $stmt = $this->db->query("SHOW COLUMNS FROM teachers LIKE 'user_id'");
            if (!$stmt->fetch()) {
                // Column missing, let's create it
                $this->db->exec("ALTER TABLE teachers ADD COLUMN user_id INT DEFAULT NULL");
                $this->db->exec("ALTER TABLE teachers ADD CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
            }
        } catch (\PDOException $e) {
            // Log but don't crash the whole app if we can't alter (e.g. permission issues)
            error_log("Database Migration Error (Teachers): " . $e->getMessage());
        }
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
