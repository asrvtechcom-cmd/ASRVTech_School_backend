<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Homework as HomeworkModel;

class HomeworkController
{
    public function __construct(private PDO $db)
    {
    }

    public function add(): void
    {
        AuthMiddleware::requireRole(['admin', 'teacher']);
        
        // Support both JSON (metadata only) and multipart/form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = Helper::getJsonInput();
        } else {
            $input = $_POST;
        }

        if (empty($input['class_id']) || empty($input['title']) || empty($input['teacher_id'])) {
            Response::json(false, 'class_id, title and teacher_id are required', null, 422);
        }

        $filePath = null;
        if (isset($_FILES['homework_file'])) {
            // Upload to Cloudinary instead of local disk
            $filePath = \App\Utils\MediaService::uploadToCloudinary($_FILES['homework_file']);
            
            if (!$filePath && $_FILES['homework_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                Response::json(false, 'Cloudinary Upload Failed. Ensure file is under 5MB and preset is valid.', null, 500);
            }
        }

        $input['file_path'] = $filePath;
        $id = (new HomeworkModel($this->db))->add($input);
        
        Response::json(true, 'add', ['id' => $id]);
    }

    public function list(): void
    {
        AuthMiddleware::authenticate();
        $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : null;
        $rows = (new HomeworkModel($this->db))->list($classId);
        
        Response::json(true, 'list', $rows);
    }
}
