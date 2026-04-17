<?php

declare(strict_types=1);

use App\Controllers\MessageController;

return function (callable $addRoute, PDO $db): void {
    $controller = new MessageController($db);

    $addRoute('POST', '/api/v1/messages/send', fn() => $controller->send());
    $addRoute('GET', '/api/v1/messages/inbox', fn() => $controller->inbox());
    $addRoute('GET', '/api/v1/messages/conversation', fn() => $controller->conversation());
};
