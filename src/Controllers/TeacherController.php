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
        $password = $input['password'] ?? 'teacher123';

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
            // 1. Create User Login
            $userId = $userModel->create($name, $email, $password, 'teacher');
            
            // 2. Link Teacher record to User
            $input['user_id'] = $userId;
            $teacherId = (new TeacherModel($this->db))->add($input);
            
            $this->db->commit();
            Response::json(true, 'add', ['id' => $teacherId, 'user_id' => $userId]);
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

        $this->db->beginTransaction();
        try {
            $teacherModel = new TeacherModel($this->db);
            $userModel = new UserModel($this->db);
            
            // 1. Find existing teacher to get user_id
            $stmt = $this->db->prepare('SELECT user_id FROM teachers WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $existing = $stmt->fetch();
            
            if ($existing && $existing['user_id']) {
                // 2. Update linked User account
                $userId = (int) $existing['user_id'];
                
                $sql = 'UPDATE users SET name = :name, email = :email';
                $params = [
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'id' => $userId
                ];
                
                if (!empty($input['password'])) {
                    $sql .= ', password = :password';
                    $params['password'] = password_hash((string)$input['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= ' WHERE id = :id';
                $this->db->prepare($sql)->execute($params);
                
                // Keep input in sync
                $input['user_id'] = $userId;
            }

            // 3. Update Teacher record
            $teacherModel->update($id, $input);
            
            $this->db->commit();
            Response::json(true, 'update', 'Teacher and login account updated successfully');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
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
