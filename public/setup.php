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
        echo "<h3>✅ Admin Account 1 Created! (admin@asrvtech.com)</h3>";
    }

    // Create Second Admin (Your Personal Email)
    $personalEmail = 'singhshubham89124@gmail.com';
    $personalPass = password_hash('123456', PASSWORD_BCRYPT);
    
    $check2 = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check2->execute([$personalEmail]);
    
    if (!$check2->fetch()) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['Shubham Admin', $personalEmail, $personalPass]);
        echo "<h3>✅ Admin Account 2 Created! ($personalEmail)</h3>";
    }

    echo "<h1>🚀 Database Ready!</h1>";
    echo "<p>You can now test the Login API in Postman using the credentials above.</p>";

} catch (PDOException $e) {
    echo "<h1>❌ Database Error</h1>";
    echo $e->getMessage();
}
