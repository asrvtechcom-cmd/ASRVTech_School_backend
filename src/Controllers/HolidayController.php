<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Holiday;

class HolidayController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();

        if (empty($input['title']) || empty($input['holiday_date'])) {
            Response::json(false, 'title and holiday_date are required', null, 422);
        }

        $id = (new Holiday($this->db))->add($input);
        Response::json(true, 'Holiday added successfully', ['id' => $id], 201);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $rows = (new Holiday($this->db))->list();
        Response::json(true, 'Holidays fetched successfully', $rows);
    }

    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'id is required', null, 422);
        }

        (new Holiday($this->db))->delete($id);
        Response::json(true, 'Holiday deleted successfully');
    }
}
