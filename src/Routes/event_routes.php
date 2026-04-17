<?php

declare(strict_types=1);

use App\Controllers\EventController;

return function (callable $addRoute, PDO $db): void {
    $controller = new EventController($db);

    $addRoute('POST', '/api/v1/events/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/events', fn() => $controller->list());
};
