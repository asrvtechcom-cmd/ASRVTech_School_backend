<?php

declare(strict_types=1);

use App\Controllers\NotificationController;

return function (callable $addRoute, PDO $db): void {
    $controller = new NotificationController($db);

    $addRoute('POST', '/api/v1/notifications/send', fn() => $controller->send());
    $addRoute('GET', '/api/v1/notifications', fn() => $controller->user());
};
