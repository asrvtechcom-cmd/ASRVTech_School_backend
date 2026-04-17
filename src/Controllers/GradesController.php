<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Grade as GradeModel;

class GradesController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        AuthMiddleware::requireRole(['admin', 'teacher']);
        $input = Helper::getJsonInput();

        if (empty($input['student_id']) || !isset($input['marks']) || empty($input['exam_name'])) {
            Response::json(false, 'student_id, exam_name and marks are required', null, 422);
        }

        $id = (new GradeModel($this->db))->add($input);
        Response::json(true, 'add', ['id' => $id]);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $studentId = (int) ($_GET['student_id'] ?? 0);
        
        $model = new GradeModel($this->db);
        if ($studentId > 0) {
            $rows = $model->byStudent($studentId);
        } else {
            AuthMiddleware::requireRole(['admin']);
            $rows = $model->all(); // I'll need to add all() to Grade model
        }

        Response::json(true, 'list', $rows);
    }
}
