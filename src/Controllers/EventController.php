<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Event;

class EventController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        $user = AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();

        if (empty($input['title']) || empty($input['event_date'])) {
            Response::json(false, 'title and event_date are required', null, 422);
        }

        $input['created_by'] = $user['user_id'];
        $id = (new Event($this->db))->add($input);
        Response::json(true, 'Event added successfully', ['id' => $id], 201);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $rows = (new Event($this->db))->list();
        Response::json(true, 'Events fetched successfully', $rows);
    }

    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'id is required', null, 422);
        }

        (new Event($this->db))->delete($id);
        Response::json(true, 'Event deleted successfully');
    }
}
