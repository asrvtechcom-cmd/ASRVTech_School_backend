<?php

declare(strict_types=1);

use App\Controllers\SubjectController;

return function (callable $addRoute, PDO $db): void {
    $controller = new SubjectController($db);

    $addRoute('POST', '/api/v1/subjects/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/subjects', fn() => $controller->list());
    $addRoute('PUT', '/api/v1/subjects/update', fn() => $controller->update());
    $addRoute('DELETE', '/api/v1/subjects/delete', fn() => $controller->delete());
};
