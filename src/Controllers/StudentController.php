<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Student as StudentModel;

class StudentController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        
        if (empty($input['name']) || empty($input['class_id'])) {
            Response::json(false, 'name and class_id are required', null, 422);
        }

        $id = (new StudentModel($this->db))->add($input);
        Response::json(true, 'add', ['id' => $id]);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $rows = (new StudentModel($this->db))->list();
        Response::json(true, 'list', $rows);
    }

    public function update(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);
        
        if ($id <= 0 || empty($input['name']) || empty($input['class_id'])) {
            Response::json(false, 'id, name and class_id are required', null, 422);
        }

        (new StudentModel($this->db))->update($id, $input);
        Response::json(true, 'update', 'Student updated successfully');
    }

    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $id = (int) ($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            Response::json(false, 'id query parameter is required', null, 422);
        }

        (new StudentModel($this->db))->delete($id);
        Response::json(true, 'delete', 'Student deleted successfully');
    }
}
