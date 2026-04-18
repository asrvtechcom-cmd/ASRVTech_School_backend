<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Mailer;
use App\Utils\Helper;
use App\Utils\Response;

// Load environment in case we are running locally, 
// though on Railway they should be in the environment already.
Helper::loadEnvFile(__DIR__ . '/../config.env');

$targetEmail = 'singhshubham29392@gmail.com';

echo "<h1>SMTP Email Test</h1>";

// --- PRE-FLIGHT CHECK ---
echo "<h3>Pre-flight Environment Check</h3>";
$host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$port = getenv('SMTP_PORT') ?: '587 (Default)';
$user = getenv('SMTP_USER') ?: 'NOT SET';
$pass = getenv('SMTP_PASS') ? 'SET (Hidden)' : 'NOT SET';

echo "<ul>";
echo "<li><strong>SMTP Host:</strong> $host</li>";
echo "<li><strong>SMTP Port:</strong> $port</li>";
echo "<li><strong>SMTP User:</strong> $user</li>";
echo "<li><strong>SMTP Pass:</strong> $pass</li>";
echo "</ul>";
echo "<hr/>";
// ------------------------

echo "Sending test email to: <strong>{$targetEmail}</strong><br/>";

try {
    // Enable verbose debug output for troubleshooting
    // Note: This will output raw SMTP conversation to the screen
    $success = Mailer::sendTestEmailWithDebug($targetEmail);

    if ($success) {
        echo "<h2 style='color: green;'>✅ SUCCESS! Email sent successfully.</h2>";
        echo "Please check the inbox (and spam folder) for <strong>{$targetEmail}</strong>.";
    } else {
        echo "<h2 style='color: red;'>❌ FAILED! Email could not be sent.</h2>";
        echo "Check your Railway logs or ensure SMTP credentials are correct.";
    }
} catch (\Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR: Exception occurred</h2>";
    echo "Message: " . $e->getMessage();
}

echo "<br/><br/><a href='/debug.php'>Back to Debug Info</a>";
