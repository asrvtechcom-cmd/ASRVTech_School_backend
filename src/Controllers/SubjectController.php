<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Subject;

class SubjectController
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

        $id = (new Subject($this->db))->add($input);
        Response::json(true, 'Subject added successfully', ['id' => $id], 201);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : null;
        $rows = (new Subject($this->db))->list($classId);
        Response::json(true, 'Subjects fetched successfully', $rows);
    }

    public function update(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0 || empty($input['name'])) {
            Response::json(false, 'id and name are required', null, 422);
        }

        (new Subject($this->db))->update($id, $input);
        Response::json(true, 'Subject updated successfully');
    }

    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'id is required', null, 422);
        }

        (new Subject($this->db))->delete($id);
        Response::json(true, 'Subject deleted successfully');
    }
}
