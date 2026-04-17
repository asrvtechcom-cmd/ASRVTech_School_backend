<?php

declare(strict_types=1);

use App\Controllers\HomeworkController;

return function (callable $addRoute, PDO $db): void {
    $controller = new HomeworkController($db);

    $addRoute('POST', '/api/v1/homework/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/homework', fn() => $controller->list());
};
