<?php

declare(strict_types=1);

use App\Controllers\ClassController;

return function (callable $addRoute, PDO $db): void {
    $controller = new ClassController($db);

    $addRoute('POST', '/api/v1/classes/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/classes', fn() => $controller->list());
    $addRoute('PUT', '/api/v1/classes/update', fn() => $controller->update());
    $addRoute('DELETE', '/api/v1/classes/delete', fn() => $controller->delete());
};
