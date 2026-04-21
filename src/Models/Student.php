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
            // List of columns to ensure exist in students table
            $columns = [
                'email' => "VARCHAR(190) UNIQUE DEFAULT NULL",
                'phone' => "VARCHAR(20) DEFAULT NULL",
                'father_name' => "VARCHAR(150) DEFAULT NULL",
                'mother_name' => "VARCHAR(150) DEFAULT NULL",
                'gender' => "VARCHAR(20) DEFAULT 'Not Specified'",
                'blood_group' => "VARCHAR(10) DEFAULT 'N/A'",
                'admission_date' => "DATE DEFAULT NULL",
                'emergency_contact' => "VARCHAR(20) DEFAULT NULL",
                'medical_notes' => "TEXT DEFAULT NULL",
                'guardian_name' => "VARCHAR(150) DEFAULT NULL",
                'password' => "VARCHAR(255) DEFAULT NULL",
                'user_id' => "INT DEFAULT NULL"
            ];

            foreach ($columns as $column => $definition) {
                $stmt = $this->db->query("SHOW COLUMNS FROM students LIKE '$column'");
                if (!$stmt->fetch()) {
                    $this->db->exec("ALTER TABLE students ADD COLUMN $column $definition");
                    
                    // Specific fix for user_id foreign key
                    if ($column === 'user_id') {
                        $this->db->exec("ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
                    }
                }
            }
        } catch (\PDOException $e) {
            error_log("Database Migration Error (Students): " . $e->getMessage());
        }
    }



    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO students (
                name, class_id, parent_id, roll_number, date_of_birth, address, photo,
                email, phone, father_name, mother_name, gender, blood_group, admission_date,
                emergency_contact, medical_notes, guardian_name, user_id, password
            )
            VALUES (
                :name, :class_id, :parent_id, :roll_number, :date_of_birth, :address, :photo,
                :email, :phone, :father_name, :mother_name, :gender, :blood_group, :admission_date,
                :emergency_contact, :medical_notes, :guardian_name, :user_id, :password
            )
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
            'mother_name' => $data['mother_name'] ?? null,
            'gender' => $data['gender'] ?? 'Not Specified',
            'blood_group' => $data['blood_group'] ?? 'N/A',
            'admission_date' => $data['admission_date'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'medical_notes' => $data['medical_notes'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null,
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
                phone = :phone, father_name = :father_name, mother_name = :mother_name,
                gender = :gender, blood_group = :blood_group, admission_date = :admission_date,
                emergency_contact = :emergency_contact, medical_notes = :medical_notes,
                guardian_name = :guardian_name, user_id = :user_id,
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
            'mother_name' => $data['mother_name'] ?? null,
            'gender' => $data['gender'] ?? 'Not Specified',
            'blood_group' => $data['blood_group'] ?? 'N/A',
            'admission_date' => $data['admission_date'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'medical_notes' => $data['medical_notes'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null,
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
