<?php

declare(strict_types=1);

use App\Controllers\GradesController;

return function (callable $addRoute, PDO $db): void {
    $controller = new GradesController($db);

    $addRoute('POST', '/api/v1/grades/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/grades', fn() => $controller->list());
};
