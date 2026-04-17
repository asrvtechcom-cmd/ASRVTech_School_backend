<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\LeaveRequest;

class LeaveRequestController
{
    public function __construct(private PDO $db)
    {
    }

    public function create(): void
    {
        $user = AuthMiddleware::authenticate();
        $input = Helper::getJsonInput();

        if (empty($input['start_date']) || empty($input['end_date'])) {
            Response::json(false, 'start_date and end_date are required', null, 422);
        }

        $input['user_id'] = $user['user_id'];
        $id = (new LeaveRequest($this->db))->create($input);
        Response::json(true, 'Leave request submitted successfully', ['id' => $id], 201);
    }

    public function list(): void
    {
        $user = AuthMiddleware::authenticate();
        $role = $user['role'] ?? '';
        
        $userId = null;
        if ($role !== 'admin') {
            $userId = (int) $user['user_id'];
        }

        $rows = (new LeaveRequest($this->db))->list($userId);
        Response::json(true, 'Leave requests fetched successfully', $rows);
    }

    public function updateStatus(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            Response::json(false, 'Valid id and status (approved/rejected) are required', null, 422);
        }

        (new LeaveRequest($this->db))->updateStatus($id, $status);
        Response::json(true, 'Leave request status updated successfully');
    }

    public function delete(): void
    {
        $user = AuthMiddleware::authenticate();
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            Response::json(false, 'id is required', null, 422);
        }

        $deleted = (new LeaveRequest($this->db))->delete($id, (int) $user['user_id']);
        if (!$deleted) {
            Response::json(false, 'Could not delete request. It might already be processed or not belong to you.', null, 400);
        }

        Response::json(true, 'Leave request deleted successfully');
    }
}
