<?php

declare(strict_types=1);

use App\Controllers\AttendanceController;

return function (callable $addRoute, PDO $db): void {
    $controller = new AttendanceController($db);

    $addRoute('POST', '/api/v1/attendance/mark', fn() => $controller->mark());
    $addRoute('GET', '/api/v1/attendance', fn() => $controller->list());
};
