<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Student
{
    public function __construct(private PDO $db)
    {
        $this->ensureUserIdColumnExists();
    }

    private function ensureUserIdColumnExists(): void
    {
        try {
            // Check if user_id column exists
            $stmt = $this->db->query("SHOW COLUMNS FROM students LIKE 'user_id'");
            if (!$stmt->fetch()) {
                // Column missing, let's create it
                $this->db->exec("ALTER TABLE students ADD COLUMN user_id INT DEFAULT NULL");
                $this->db->exec("ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
            }
        } catch (\PDOException $e) {
            // Log but don't crash
            error_log("Database Migration Error (Students): " . $e->getMessage());
        }
    }


    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO students (name, class_id, parent_id, roll_number, date_of_birth, address, photo, email, phone, father_name, user_id, password)
            VALUES (:name, :class_id, :parent_id, :roll_number, :date_of_birth, :address, :photo, :email, :phone, :father_name, :user_id, :password)
        ');
        $stmt->execute([
            'name' => $data['name'],
            'class_id' => $data['class_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'roll_number' => $data['roll_number'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'address' => $data['address'] ?? null,
            'photo' => $data['photo'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'password' => $data['password'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(): array
    {
        $stmt = $this->db->query('
            SELECT s.*, c.name AS class_name, u.name AS parent_name
            FROM students s
            LEFT JOIN classes c ON c.id = s.class_id
            LEFT JOIN users u ON u.id = s.parent_id
            ORDER BY s.id DESC
        ');
        return $stmt->fetchAll();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('
            UPDATE students
            SET name = :name, class_id = :class_id, parent_id = :parent_id,
                roll_number = :roll_number, date_of_birth = :date_of_birth, 
                address = :address, photo = :photo, email = :email, 
                phone = :phone, father_name = :father_name, user_id = :user_id,
                password = :password
            WHERE id = :id
        ');
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'class_id' => $data['class_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'roll_number' => $data['roll_number'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'address' => $data['address'] ?? null,
            'photo' => $data['photo'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'father_name' => $data['father_name'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'password' => $data['password'] ?? null,
        ]);
    }


    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM students WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
