<?php

declare(strict_types=1);

use App\Controllers\AuthController;

return function (callable $addRoute, PDO $db): void {
    $controller = new AuthController($db);

    $addRoute('POST', '/api/v1/login', fn() => $controller->login());
    $addRoute('POST', '/api/v1/logout', fn() => $controller->logout());
    $addRoute('POST', '/api/v1/forgot-password', fn() => $controller->forgotPassword());
    $addRoute('POST', '/api/v1/reset-password', fn() => $controller->resetPassword());
    $addRoute('POST', '/api/v1/user/update-token', fn() => $controller->updateToken());
};
