<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Utils/Helper.php';
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Models/User.php';

use App\Utils\Helper;
use App\Config\Database;
use App\Models\User as UserModel;

// 1. Initialise environment
Helper::loadEnvFile(__DIR__ . '/../config.env');
$token = $_GET['token'] ?? '';

$error = '';
$success = '';

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    try {
        $db = (new Database())->connect();
        $userModel = new UserModel($db);
        $reset = $userModel->getValidResetByToken($token);

        if (!$reset) {
            $error = 'This reset link has expired or reached its usage limit.';
        }

        // Handle Form Submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
            $pass1 = $_POST['pass1'] ?? '';
            $pass2 = $_POST['pass2'] ?? '';

            if (strlen($pass1) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } elseif ($pass1 !== $pass2) {
                $error = 'Passwords do not match.';
            } else {
                // Perform Reset
                $db->beginTransaction();
                try {
                    $userModel->updatePassword((int) $reset['user_id'], $pass1);
                    $userModel->markResetTokenUsed((int) $reset['reset_id']);
                    $db->commit();
                    $success = 'Password reset successfully! You can now log in to the app.';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Server error. Please try again later.';
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Database connection error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | ASRV Kindergarten</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #8b5cf6;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 { font-size: 28px; margin-bottom: 10px; font-weight: 600; }
        p { color: #94a3b8; margin-bottom: 30px; font-size: 15px; }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5);
        }

        button:active { transform: translateY(0); }

        .alert {
            padding: 14px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; }

        .bg-blobs {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1;
            filter: blur(80px);
            opacity: 0.4;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            background: var(--primary);
        }

        .blob-1 { width: 300px; height: 300px; top: -100px; right: -100px; animation: float 10s infinite; }
        .blob-2 { width: 400px; height: 400px; bottom: -150px; left: -150px; background: var(--accent); animation: float 15s infinite reverse; }

        @keyframes float {
            0% { transform: translate(0,0); }
            50% { transform: translate(30px, 40px); }
            100% { transform: translate(0,0); }
        }
    </style>
</head>
<body>
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="container">
        <div class="card">
            <h1>Reset Password</h1>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p>Redirecting you back to the app...</p>
            <?php else: ?>
                <p>Enter your new password below to regain access to your account.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!$error || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <form method="POST">
                        <div class="input-group">
                            <label>New Password</label>
                            <input type="password" name="pass1" placeholder="••••••••" required autofocus>
                        </div>
                        <div class="input-group">
                            <label>Confirm Password</label>
                            <input type="password" name="pass2" placeholder="••••••••" required>
                        </div>
                        <button type="submit">Update Password</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
