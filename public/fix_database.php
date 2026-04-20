<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load Environment
$envFile = __DIR__ . '/../config.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Database Connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? '';
$pass = $_ENV['DB_PASS'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERR_MODE => PDO::ATTR_ERR_MODE_EXCEPTION
    ]);

    echo "<h1>Database Fix Script</h1>";

    // 1. Check/Fix Students Table
    echo "Checking 'students' table... ";
    $cols = $pdo->query("SHOW COLUMNS FROM students LIKE 'user_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE students ADD COLUMN user_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE students ADD CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "<b style='color:green'>Fixed! (Added user_id)</b><br>";
    } else {
        echo "<b style='color:blue'>OK</b><br>";
    }

    // 2. Check/Fix Teachers Table
    echo "Checking 'teachers' table... ";
    $cols = $pdo->query("SHOW COLUMNS FROM teachers LIKE 'user_id'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE teachers ADD COLUMN user_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE teachers ADD CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "<b style='color:green'>Fixed! (Added user_id)</b><br>";
    } else {
        echo "<b style='color:blue'>OK</b><br>";
    }

    echo "<br><h2 style='color:green'>Success! Your database is now up to date.</h2>";
    echo "<p>You can now go back to the app and save teacher details.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
}
