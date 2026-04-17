<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Fee as FeeModel;
use App\Services\NotificationService;

class FeesController
{
    public function __construct(private PDO $db)
    {
    }

    public function create(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();

        if (empty($input['student_id']) || !isset($input['amount'])) {
            Response::json(false, 'student_id and amount are required', null, 422);
        }

        $model = new FeeModel($this->db);
        $id = $model->add($input);

        // Automated Notification
        (new NotificationService($this->db))->sendFeeAlert(
            (int) $input['student_id'], 
            (string) $input['amount'], 
            (string) ($input['due_date'] ?? 'N/A')
        );

        Response::json(true, 'create', ['id' => $id]);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $studentId = (int) ($_GET['student_id'] ?? 0);
        $model = new FeeModel($this->db);

        if ($studentId > 0) {
            $rows = $model->byStudent($studentId);
        } else {
            AuthMiddleware::requireRole(['admin']);
            $rows = $model->all();
        }

        Response::json(true, 'list', $rows);
    }

    public function pay(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'id is required', null, 422);
        }

        $model = new FeeModel($this->db);
        $success = $model->pay($id);

        if (!$success) {
            Response::json(false, 'Fee record not found or update failed', null, 404);
        }

        Response::json(true, 'pay', 'Fee marked as paid');
    }
}
