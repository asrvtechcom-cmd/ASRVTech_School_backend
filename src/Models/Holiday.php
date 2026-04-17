<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Holiday
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO holidays (title, holiday_date, description)
            VALUES (:title, :holiday_date, :description)
        ');
        $stmt->execute([
            'title' => $data['title'],
            'holiday_date' => $data['holiday_date'],
            'description' => $data['description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(): array
    {
        $stmt = $this->db->query('SELECT * FROM holidays ORDER BY holiday_date ASC');
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM holidays WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
