<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Event
{
    public function __construct(private PDO $db)
    {
    }

    public function add(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO events (title, description, event_date, created_by)
            VALUES (:title, :description, :event_date, :created_by)
        ');
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'event_date' => $data['event_date'],
            'created_by' => $data['created_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(): array
    {
        $stmt = $this->db->query('SELECT * FROM events ORDER BY event_date ASC');
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM events WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
