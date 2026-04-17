<?php

declare(strict_types=1);

use App\Controllers\HolidayController;

return function (callable $addRoute, PDO $db): void {
    $controller = new HolidayController($db);

    $addRoute('POST', '/api/v1/holidays/add', fn() => $controller->add());
    $addRoute('GET', '/api/v1/holidays', fn() => $controller->list());
};
