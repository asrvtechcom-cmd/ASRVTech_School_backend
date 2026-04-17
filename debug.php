<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h1>PHP Debug Info</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";
echo "<h2>Environment Variables:</h2>";
echo "<pre>";
echo "DB_HOST = " . (getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_NAME = " . (getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "DB_USER = " . (getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "DB_PASS = " . (getenv('DB_PASS') ? 'SET (hidden)' : ($_ENV['DB_PASS'] ?? 'NOT SET')) . "\n";
echo "DB_PORT = " . (getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h2>Testing Autoloader...</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:green'>✅ vendor/autoload.php found!</p>";
} else {
    echo "<p style='color:red'>❌ vendor/autoload.php NOT FOUND!</p>";
}

echo "<h2>Testing DB Connection...</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $db = (new \App\Config\Database())->connect();
    echo "<p style='color:green'>✅ Database connected successfully!</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>❌ DB Error: " . $e->getMessage() . "</p>";
}
