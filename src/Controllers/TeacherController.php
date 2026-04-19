<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Teacher as TeacherModel;
use App\Models\User as UserModel;

class TeacherController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');

        if ($name === '' || $email === '') {
            Response::json(false, 'name and email are required', null, 422);
        }

        // Handle Photo Upload
        if (isset($_FILES['photo'])) {
            $photoUrl = \App\Utils\MediaService::uploadToCloudinary($_FILES['photo']);
            if ($photoUrl) {
                $input['photo'] = $photoUrl;
            }
        }

        $userModel = new UserModel($this->db);
        if ($userModel->findByEmail($email)) {
            Response::json(false, 'A user with this email already exists', null, 400);
        }

        $this->db->beginTransaction();
        try {
            $teacherId = (new TeacherModel($this->db))->add($input);
            $userModel->create($name, $email, 'teacher123', 'teacher');
            $this->db->commit();
            
            Response::json(true, 'add', ['id' => $teacherId]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $rows = (new TeacherModel($this->db))->list();
        Response::json(true, 'list', $rows);
    }

    public function update(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $input = Helper::getJsonInput();
        $id = (int) ($input['id'] ?? 0);
        
        if ($id <= 0 || empty($input['name']) || empty($input['email'])) {
            Response::json(false, 'id, name and email are required', null, 422);
        }

        // Handle Photo Upload
        if (isset($_FILES['photo'])) {
            $photoUrl = \App\Utils\MediaService::uploadToCloudinary($_FILES['photo']);
            if ($photoUrl) {
                $input['photo'] = $photoUrl;
            }
        }

        (new TeacherModel($this->db))->update($id, $input);
        Response::json(true, 'update', 'Teacher updated successfully');
    }

    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $id = (int) ($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            Response::json(false, 'id query parameter is required', null, 422);
        }

        (new TeacherModel($this->db))->delete($id);
        Response::json(true, 'delete', 'Teacher deleted successfully');
    }
}
