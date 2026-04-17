<?php

declare(strict_types=1);

// When served from public/, the root is one directory up
$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

use App\Utils\Helper;
use App\Utils\Response;
use App\Config\Database;

// 1. CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 2. Setup Environment & DB
Helper::loadEnvFile($root . '/config.env');

set_exception_handler(function (Throwable $e): void {
    Response::json(false, 'Server Error', $e->getMessage(), 500);
});

$db = (new Database())->connect();

// 3. Routing System
$routes = [];
$addRoute = function (string $method, string $path, callable $handler) use (&$routes): void {
    if (!str_starts_with($path, '/api/v1/')) {
        $path = '/api/v1' . ($path === '/' ? '' : $path);
    }
    $routes[strtoupper($method)][$path] = $handler;
};

// 4. Load Route Definitions
$routeFiles = [
    $root . '/src/Routes/auth_routes.php',
    $root . '/src/Routes/student_routes.php',
    $root . '/src/Routes/teacher_routes.php',
    $root . '/src/Routes/attendance_routes.php',
    $root . '/src/Routes/homework_routes.php',
    $root . '/src/Routes/grades_routes.php',
    $root . '/src/Routes/fees_routes.php',
    $root . '/src/Routes/message_routes.php',
    $root . '/src/Routes/notification_routes.php',
    $root . '/src/Routes/class_routes.php',
    $root . '/src/Routes/subject_routes.php',
    $root . '/src/Routes/event_routes.php',
    $root . '/src/Routes/holiday_routes.php',
    $root . '/src/Routes/leave_request_routes.php',
];

foreach ($routeFiles as $file) {
    if (file_exists($file)) {
        $register = require $file;
        $register($addRoute, $db);
    }
}

// 5. Dispatch
$method = strtoupper($_SERVER['REQUEST_METHOD']);
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($requestPath, $scriptDir)) {
    $requestPath = substr($requestPath, strlen($scriptDir));
}
$requestPath = $requestPath === '' ? '/' : $requestPath;

if (!isset($routes[$method][$requestPath])) {
    Response::json(false, 'Route Not Found', [
        'method' => $method,
        'path' => $requestPath,
    ], 404);
}

// Execute Handler
$routes[$method][$requestPath]();
