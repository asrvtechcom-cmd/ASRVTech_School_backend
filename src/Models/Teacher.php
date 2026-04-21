<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Teacher
{
    public function __construct(private PDO $db)
    {
        $this->ensureSchemaUpdated();
    }

    private function ensureSchemaUpdated(): void
    {
        try {
            // 1. Initial user_id check
            $stmt = $this->db->query("SHOW COLUMNS FROM teachers LIKE 'user_id'");
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE teachers ADD COLUMN user_id INT DEFAULT NULL");
                $this->db->exec("ALTER TABLE teachers ADD CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
            }

            // 2. Add expanded professional and personal details
            $newColumns = [
                'gender' => "VARCHAR(20) DEFAULT 'Not Specified'",
                'blood_group' => "VARCHAR(10) DEFAULT 'N/A'",
                'designation' => "VARCHAR(100) DEFAULT 'Teacher'",
                'bio' => "TEXT DEFAULT NULL",
                'qualification' => "VARCHAR(255) DEFAULT 'Not Specified'",
                'experience' => "VARCHAR(20) DEFAULT '0'",
                'emergency_contact' => "VARCHAR(20) DEFAULT NULL",
                'address' => "TEXT DEFAULT NULL"
            ];

            foreach ($newColumns as $column => $definition) {
                $check = $this->db->query("SHOW COLUMNS FROM teachers LIKE '$column'");
                if (!$check->fetch()) {
                    $this->db->exec("ALTER TABLE teachers ADD COLUMN $column $definition");
                }
            }
        } catch (\PDOException $e) {
            error_log("Database Migration Error (Teachers Expanded): " . $e->getMessage());
        }
    }


    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO teachers (
                name, email, phone, subject, photo, user_id, 
                gender, blood_group, designation, bio, 
                qualification, experience, emergency_contact, address
            )
            VALUES (
                :name, :email, :phone, :subject, :photo, :user_id,
                :gender, :blood_group, :designation, :bio,
                :qualification, :experience, :emergency_contact, :address
            )
        ');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'photo' => $data['photo'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'gender' => $data['gender'] ?? 'Not Specified',
            'blood_group' => $data['blood_group'] ?? 'N/A',
            'designation' => $data['designation'] ?? 'Teacher',
            'bio' => $data['bio'] ?? null,
            'qualification' => $data['qualification'] ?? 'Not Specified',
            'experience' => $data['experience'] ?? '0',
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'address' => $data['address'] ?? null,
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
            SET 
                name = :name, 
                email = :email, 
                phone = :phone, 
                subject = :subject, 
                photo = :photo, 
                user_id = :user_id,
                gender = :gender,
                blood_group = :blood_group,
                designation = :designation,
                bio = :bio,
                qualification = :qualification,
                experience = :experience,
                emergency_contact = :emergency_contact,
                address = :address
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
            'gender' => $data['gender'] ?? 'Not Specified',
            'blood_group' => $data['blood_group'] ?? 'N/A',
            'designation' => $data['designation'] ?? 'Teacher',
            'bio' => $data['bio'] ?? null,
            'qualification' => $data['qualification'] ?? 'Not Specified',
            'experience' => $data['experience'] ?? '0',
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }


    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM teachers WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
