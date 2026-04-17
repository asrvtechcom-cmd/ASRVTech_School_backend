<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap minimal DB connection
$env = parse_ini_file(__DIR__ . '/.env');
if (!$env) {
    die(".env file not found or invalid\n");
}

$dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};charset={$env['DB_CHARSET']}";
try {
    $db = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Use the existing User Model
require_once __DIR__ . '/src/Models/User.php';
use App\Models\User;

$userModel = new User($db);

$email = 'admin@example.com';
$password = 'password123';

// Check if user already exists
if ($userModel->findByEmail($email)) {
    echo "User '$email' already exists!\n";
    exit;
}

// Create admin user
$id = $userModel->create(
    'System Admin',
    $email,
    $password,
    'admin'
);

echo "------------------------------------------\n";
echo "SUCCESS: Admin user created!\n";
echo "Email: $email\n";
echo "Password: $password\n";
echo "User ID: $id\n";
echo "------------------------------------------\n";
