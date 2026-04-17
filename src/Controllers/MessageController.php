<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Utils\Helper;
use App\Utils\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Message as MessageModel;

class MessageController
{
    public function __construct(private PDO $db)
    {
    }

    public function send(): void
    {
        $user = AuthMiddleware::authenticate();
        $input = Helper::getJsonInput();

        $receiverId = (int) ($input['receiver_id'] ?? 0);
        $message = trim($input['message'] ?? '');

        if ($receiverId <= 0 || $message === '') {
            Response::json(false, 'receiver_id and message are required', null, 422);
        }

        $model = new MessageModel($this->db);
        $id = $model->send((int) $user['user_id'], $receiverId, $message);

        Response::json(true, 'send', ['id' => $id]);
    }

    public function inbox(): void
    {
        $user = AuthMiddleware::authenticate();
        $model = new MessageModel($this->db);
        $conversations = $model->inbox((int) $user['user_id']);

        Response::json(true, 'inbox', $conversations);
    }

    public function conversation(): void
    {
        $user = AuthMiddleware::authenticate();
        $otherUserId = (int) ($_GET['user_id'] ?? 0);

        if ($otherUserId <= 0) {
            Response::json(false, 'user_id query parameter is required', null, 422);
        }

        $model = new MessageModel($this->db);
        $messages = $model->conversation((int) $user['user_id'], $otherUserId);
        $model->markAsRead((int) $user['user_id'], $otherUserId);

        Response::json(true, 'conversation', $messages);
    }
}
