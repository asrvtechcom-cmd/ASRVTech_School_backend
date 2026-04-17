<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h1>PHP Debug Info</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";
echo "<br/><b>Database Configuration (as interpreted by app):</b><br/>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $db = new \App\Config\Database();
    
    // Use reflection to peek at private properties for debugging
    $reflection = new ReflectionClass($db);
    $props = ['host', 'port', 'dbName', 'username'];
    foreach ($props as $pName) {
        $prop = $reflection->getProperty($pName);
        $prop->setAccessible(true);
        $val = $prop->getValue($db);
        echo "Configured " . ucfirst($pName) . ": " . ($val ?: '<i>empty</i>') . "<br/>";
    }
} catch (Exception $e) {
    echo "Error inspecting database config: " . $e->getMessage();
}

echo "<br/>To check actual DB connection, visit setup.php";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $db = (new \App\Config\Database())->connect();
    echo "<p style='color:green'>✅ Database connected successfully!</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>❌ DB Error: " . $e->getMessage() . "</p>";
}
