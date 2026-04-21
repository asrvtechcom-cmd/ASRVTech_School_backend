<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    private static bool $roleEnumEnsured = false;

    public function __construct(private PDO $db)
    {
        $this->ensureRoleEnumIncludesStudent();
    }

    private function ensureRoleEnumIncludesStudent(): void
    {
        if (self::$roleEnumEnsured) {
            return;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'role'");
            $column = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            $type = strtolower((string) ($column['Type'] ?? ''));

            if ($type !== '' && !str_contains($type, "'student'")) {
                $this->db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','teacher','parent','student') NOT NULL DEFAULT 'parent'");
            }
        } catch (\Throwable $e) {
            error_log('User model migration warning (role enum): ' . $e->getMessage());
        }

        self::$roleEnumEnsured = true;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $name, string $email, string $password, string $role): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        return $stmt->execute([
            'password' => password_hash($newPassword, PASSWORD_BCRYPT),
            'id' => $id,
        ]);
    }

    public function storeResetToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (:user_id, :token, :expires_at)
        ');
        return $stmt->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    public function invalidateActiveResetTokensForUser(int $userId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE password_resets
            SET used_at = NOW()
            WHERE user_id = :user_id AND used_at IS NULL
        ');
        return $stmt->execute(['user_id' => $userId]);
    }

    public function deleteResetTokenByValue(string $token): bool
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE token = :token');
        return $stmt->execute(['token' => $token]);
    }

    public function getValidResetByToken(string $token): ?array
    {
        $stmt = $this->db->prepare('
            SELECT pr.id as reset_id, pr.user_id, pr.expires_at, u.email
            FROM password_resets pr
            INNER JOIN users u ON u.id = pr.user_id
            WHERE pr.token = :token AND pr.used_at IS NULL
            LIMIT 1
        ');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    public function markResetTokenUsed(int $resetId): bool
    {
        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $resetId]);
    }
}
