<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Utils/Helper.php';
require_once __DIR__ . '/../src/Config/Database.php';

use App\Utils\Helper;
use App\Config\Database;

// 1. Load Env
Helper::loadEnvFile(__DIR__ . '/../config.env');

echo "<h2>Database Migration: Adding Homework File Support</h2>";

try {
    $db = (new Database())->connect();
    
    // Check if column exists already
    $check = $db->query("SHOW COLUMNS FROM homework LIKE 'file_path'");
    if ($check->fetch()) {
        echo "<p style='color: orange;'>Column 'file_path' already exists in 'homework' table.</p>";
    } else {
        // Add the column
        $db->exec("ALTER TABLE homework ADD COLUMN file_path VARCHAR(255) DEFAULT NULL AFTER due_date");
        echo "<p style='color: green;'>✅ Success: Column 'file_path' added to 'homework' table!</p>";
    }

    echo "<h3>🚀 Migration Complete!</h3>";
    echo "<p>Your backend can now store Homework PDFs on Cloudinary. I will now delete this file for security.</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
