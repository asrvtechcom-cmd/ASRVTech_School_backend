<?php
// setup.php - Quick DB Setup Script
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Config/Database.php';

try {
    $db = (new \App\Config\Database())->connect();
    
    $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    
    if (!$sql) {
        die("Could not read schema.sql");
    }

    $db->exec($sql);
    
    // Create a Default Admin User
    $adminEmail = 'admin@asrvtech.com';
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $adminName = 'ASRV Admin';
    
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$adminEmail]);
    
    if (!$check->fetch()) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$adminName, $adminEmail, $adminPass]);
        echo "<h3>✅ Default Admin Account Created!</h3>";
        echo "<ul><li><strong>Email:</strong> admin@asrvtech.com</li><li><strong>Password:</strong> admin123</li></ul>";
    }

    echo "<h1>🚀 Database Ready!</h1>";
    echo "<p>You can now test the Login API in Postman using the credentials above.</p>";

} catch (PDOException $e) {
    echo "<h1>❌ Database Error</h1>";
    echo $e->getMessage();
}
