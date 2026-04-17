<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Response;

class RoleMiddleware
{
    /**
     * Check if user has the required role(s)
     */
    public static function check(array $user, array|string $allowedRoles): void
    {
        $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        $userRole = $user['role'] ?? '';

        if (!in_array($userRole, $roles, true)) {
            Response::json(false, 'Forbidden: You do not have permission to access this resource', null, 403);
        }
    }

    /**
     * Ensure parents can only READ data (GET requests)
     */
    public static function restrictParentMutation(array $user): void
    {
        if (($user['role'] ?? '') === 'parent') {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($method !== 'GET') {
                Response::json(false, 'Forbidden: Parents cannot create or modify school data', null, 403);
            }
        }
    }
}
