<?php

declare(strict_types=1);

use App\Controllers\FeesController;

return function (callable $addRoute, PDO $db): void {
    $controller = new FeesController($db);

    $addRoute('POST', '/api/v1/fees/create', fn() => $controller->create());
    $addRoute('GET', '/api/v1/fees', fn() => $controller->list());
    $addRoute('POST', '/api/v1/fees/pay', fn() => $controller->pay());
};
