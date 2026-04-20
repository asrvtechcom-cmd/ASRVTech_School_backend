<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from config.env (path relative to script)
$envPath = __DIR__ . '/../config.env';
if (!file_exists($envPath)) {
    die("config.env not found at $envPath\n");
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = explode('=', $line, 2);
    putenv(trim($name) . '=' . trim($value));
}

$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbPort = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Connected to database successfully.\n";

    // 1. Update users.role ENUM (Add 'student')
    echo "Updating users.role enum...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'parent', 'student') NOT NULL DEFAULT 'parent'");

    // 2. Add columns to students table
    echo "Adding columns to students table...\n";
    
    $cols = [
        "email" => "VARCHAR(190) UNIQUE DEFAULT NULL",
        "phone" => "VARCHAR(20) DEFAULT NULL",
        "father_name" => "VARCHAR(150) DEFAULT NULL",
        "user_id" => "INT DEFAULT NULL",
        "password" => "VARCHAR(255) DEFAULT NULL" // For reference, though we use users table
    ];

    foreach ($cols as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE students ADD COLUMN $col $definition");
            echo "Added $col to students table.\n";
        } catch (Exception $e) {
            echo "Column $col might already exist: " . $e->getMessage() . "\n";
        }
    }

    // 3. Add Foreign Key if missing
    try {
        $pdo->exec("ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added foreign key constraint users(id) to students.\n";
    } catch (Exception $e) {
        echo "Constraint might already exist.\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
