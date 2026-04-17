<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h1>PHP Debug Info</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";
echo "<b>Loaded Environment Variables (Server context):</b><br/>";
echo "MYSQL_URL = " . (getenv('MYSQL_URL') ? 'SET (' . substr(getenv('MYSQL_URL'), 0, 15) . '...)' : 'NOT SET') . "<br/>";
echo "MYSQL_PUBLIC_URL = " . (getenv('MYSQL_PUBLIC_URL') ? 'SET' : 'NOT SET') . "<br/>";
echo "DB_HOST = " . (getenv('DB_HOST') ? getenv('DB_HOST') : 'NOT SET') . "<br/>";
echo "DB_PORT = " . (getenv('DB_PORT') ? getenv('DB_PORT') : 'NOT SET') . "<br/>";

echo "<br/><b>Parsed Connection Output:</b><br/>";
$url = getenv('MYSQL_URL');
if ($url) {
    $parsed = parse_url($url);
    echo "Parsed Host: " . ($parsed['host'] ?? 'N/A') . "<br/>";
    echo "Parsed Port: " . ($parsed['port'] ?? 'N/A') . "<br/>";
    echo "Parsed User: " . ($parsed['user'] ?? 'N/A') . "<br/>";
} else {
    echo "No MYSQL_URL to parse.<br/>";
}

echo "<br/>To check actual DB connection, visit setup.php";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $db = (new \App\Config\Database())->connect();
    echo "<p style='color:green'>✅ Database connected successfully!</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>❌ DB Error: " . $e->getMessage() . "</p>";
}
