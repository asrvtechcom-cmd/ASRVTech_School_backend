<?php

declare(strict_types=1);

use App\Controllers\StudentController;

return function (callable $addRoute, PDO $db): void {
    $controller = new StudentController($db);

    $addRoute('POST', '/api/v1/students/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/students', fn() => $controller->list());
    $addRoute('PUT', '/api/v1/students/update', fn() => $controller->update());
    $addRoute('DELETE', '/api/v1/students/delete', fn() => $controller->delete());
};
