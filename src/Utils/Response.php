<?php

declare(strict_types=1);

namespace App\Utils;

class Response
{
    public static function json(bool $success, string $messageOrDataName, mixed $data = null, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'status' => $success ? 'success' : 'error'
        ];

        if ($success) {
            $response['data'] = $data ?? [$messageOrDataName => true];
        } else {
            $response['message'] = $messageOrDataName;
            if ($data !== null) {
                $response['error_details'] = $data;
            }
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
