<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\SchoolClass;

class ClassController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();

        if (empty($input['name'])) {
            Response::json(false, 'name is required', null, 422);
        }

        $id = (new SchoolClass($this->db))->add($input);
        Response::json(true, 'Class added successfully', ['id' => $id], 201);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $rows = (new SchoolClass($this->db))->list();
        Response::json(true, 'Classes fetched successfully', $rows);
    }

    public function update(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0 || empty($input['name'])) {
            Response::json(false, 'id and name are required', null, 422);
        }

        (new SchoolClass($this->db))->update($id, $input);
        Response::json(true, 'Class updated successfully');
    }

    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'id is required', null, 422);
        }

        (new SchoolClass($this->db))->delete($id);
        Response::json(true, 'Class deleted successfully');
    }
}
