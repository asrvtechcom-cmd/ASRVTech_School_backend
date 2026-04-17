<?php

declare(strict_types=1);

use App\Controllers\TeacherController;

return function (callable $addRoute, PDO $db): void {
    $controller = new TeacherController($db);

    $addRoute('POST', '/api/v1/teachers/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/teachers', fn() => $controller->list());
    $addRoute('PUT', '/api/v1/teachers/update', fn() => $controller->update());
    $addRoute('DELETE', '/api/v1/teachers/delete', fn() => $controller->delete());
};
