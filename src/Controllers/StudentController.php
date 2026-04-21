<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Student as StudentModel;
use App\Models\User as UserModel;

class StudentController
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
        $password = $input['password'] ?? 'student123';
        
        if (empty($name) || empty($input['class_id']) || empty($email)) {
            Response::json(false, 'name, email and class_id are required', null, 422);
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
            // 1. Create User login account
            $userId = $userModel->create($name, $email, $password, 'student');
            $input['user_id'] = $userId;
            
            // 2. Create Student record
            $studentId = (new StudentModel($this->db))->add($input);
            
            $this->db->commit();
            Response::json(true, 'Student added successfully', ['id' => $studentId]);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
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
        
        if ($id <= 0 || empty($input['name'])) {
            Response::json(false, 'id and name are required', null, 422);
        }

        // Handle Photo Upload
        if (isset($_FILES['photo'])) {
            $photoUrl = \App\Utils\MediaService::uploadToCloudinary($_FILES['photo']);
            if ($photoUrl) {
                $input['photo'] = $photoUrl;
            }
        }

        $stmt = $this->db->prepare('SELECT * FROM students WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            Response::json(false, 'Student not found', null, 404);
        }

        // Preserve already saved values when partial payload is sent.
        $payload = array_merge((array) $existing, $input);

        (new StudentModel($this->db))->update($id, $payload);
        Response::json(true, 'update', 'Student updated successfully');
    }


    public function delete(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $id = (int) ($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            Response::json(false, 'id query parameter is required', null, 422);
        }

        $studentModel = new StudentModel($this->db);
        
        // 1. Find the student to get their user_id
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            Response::json(false, 'Student not found', null, 404);
        }

        $userId = $student['user_id'] ? (int) $student['user_id'] : null;

        $this->db->beginTransaction();
        try {
            // 2. Delete Student Record
            $studentModel->delete($id);
            
            // 3. Delete associated User record if exists
            if ($userId) {
                (new UserModel($this->db))->delete($userId);
            }
            
            $this->db->commit();
            Response::json(true, 'Student and associated user account deleted successfully', null);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
