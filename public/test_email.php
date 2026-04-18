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
echo "<h3>Pre-flight Environment Check (API Mode)</h3>";
$apiKey = getenv('RESEND_API_KEY') ? 'SET (Hidden)' : 'NOT SET';
// We force this in Mailer.php for now
$from = 'onboarding@resend.dev'; 

echo "<ul>";
echo "<li><strong>Resend API Key:</strong> $apiKey</li>";
echo "<li><strong>From Email (Forced):</strong> $from</li>";
echo "</ul>";
echo "<p style='color: orange;'><strong>IMPORTANT:</strong> You can only send TO the email address you used to sign up for Resend!</p>";
echo "<hr/>";
// ------------------------

echo "Sending test email to: <strong>{$targetEmail}</strong><br/>";

try {
    // Measure time taken
    $startTime = microtime(true);
    
    // Enable verbose debug output for troubleshooting
    $success = Mailer::sendTestEmailWithDebug($targetEmail);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    if ($success) {
        echo "<h2 style='color: green;'>✅ SUCCESS! Email sent successfully.</h2>";
        echo "Time taken: <strong>{$duration} seconds</strong><br/>";
        echo "Please check the inbox (and spam folder) for <strong>{$targetEmail}</strong>.";
    } else {
        echo "<h2 style='color: red;'>❌ FAILED! Email could not be sent.</h2>";
        echo "Time taken: <strong>{$duration} seconds</strong> (Failed)<br/>";
        echo "Check your Railway logs or ensure SMTP credentials are correct.";
    }
} catch (\Exception $e) {
    echo "<h2 style='color: red;'>❌ ERROR: Exception occurred</h2>";
    echo "Message: " . $e->getMessage();
}

echo "<br/><br/><a href='/debug.php'>Back to Debug Info</a>";
