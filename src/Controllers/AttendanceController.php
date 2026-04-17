<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Attendance as AttendanceModel;
use App\Services\NotificationService;

class AttendanceController
{
    public function __construct(private PDO $db)
    {
    }

    public function mark(): void
    {
        $user = AuthMiddleware::requireRole(['admin', 'teacher']);
        $input = Helper::getJsonInput();
        
        $studentId = (int) ($input['student_id'] ?? 0);
        $date = $input['date'] ?? date('Y-m-d');
        $status = $input['status'] ?? '';
        $teacherId = (int) ($user['user_id']);

        if ($studentId <= 0 || !in_array($status, ['present', 'absent', 'late'], true)) {
            Response::json(false, 'Valid student_id and status (present/absent/late) are required', null, 422);
        }

        $model = new AttendanceModel($this->db);
        $model->mark($studentId, $date, $status, $teacherId);

        // Automated Absent Notification
        if ($status === 'absent') {
            (new NotificationService($this->db))->sendAbsenteeAlert($studentId, $date);
        }

        Response::json(true, 'mark', 'Attendance marked successfully');
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $studentId = (int) ($_GET['student_id'] ?? 0);
        $classId = (int) ($_GET['class_id'] ?? 0);

        $model = new AttendanceModel($this->db);
        
        if ($studentId > 0) {
            $rows = $model->byStudent($studentId);
        } elseif ($classId > 0) {
            $date = $_GET['date'] ?? date('Y-m-d');
            $rows = $model->byClass($classId, $date);
        } else {
            Response::json(false, 'student_id or class_id is required', null, 422);
        }

        Response::json(true, 'list', $rows);
    }
}
