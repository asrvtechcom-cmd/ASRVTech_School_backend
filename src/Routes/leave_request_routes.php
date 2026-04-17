<?php

declare(strict_types=1);

use App\Controllers\LeaveRequestController;

return function (callable $addRoute, PDO $db): void {
    $controller = new LeaveRequestController($db);

    $addRoute('POST', '/api/v1/leaves/apply', fn() => $controller->apply());
    $addRoute('GET', '/api/v1/leaves', fn() => $controller->list());
    $addRoute('PATCH', '/api/v1/leaves/status', fn() => $controller->updateStatus());
};
