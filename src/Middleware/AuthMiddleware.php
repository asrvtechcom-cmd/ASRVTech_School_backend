<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Helper;
use App\Utils\Response;

class AuthMiddleware
{
    public static function authenticate(): array
    {
        $token = Helper::getBearerToken();
        if (!$token) {
            Response::json(false, 'Authorization token missing', null, 401);
        }

        $secret = getenv('JWT_SECRET') ?: 'change_this_secret_key';
        $payload = Helper::verifyJWT($token, $secret);
        if (!$payload) {
            Response::json(false, 'Invalid or expired token', null, 401);
        }

        return $payload;
    }

    public static function requireRole(array|string $allowedRoles): array
    {
        $user = self::authenticate();
        $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        if (!in_array($user['role'] ?? '', $roles, true)) {
            Response::json(false, 'Forbidden', null, 403);
        }
        return $user;
    }
}
