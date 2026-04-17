<?php
// setup.php - Quick DB Setup Script
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config/Database.php';

try {
    $db = (new \App\Config\Database())->connect();
    
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    
    if (!$sql) {
        die("Could not read schema.sql");
    }

    $db->exec($sql);
    echo "<h1>✅ Database Tables Created Successfully!</h1>";
    echo "<p>You can now delete this file and start using the API.</p>";

} catch (PDOException $e) {
    echo "<h1>❌ Database Error</h1>";
    echo $e->getMessage();
}
