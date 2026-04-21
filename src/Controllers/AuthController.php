<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Throwable;
use App\Models\User as UserModel;
use App\Middleware\AuthMiddleware;
use App\Utils\Helper;
use App\Utils\Response;
use App\Utils\Mailer;

class AuthController
{
    public function __construct(private PDO $db)
    {
    }

    public function register(): void
    {
        $input = Helper::getJsonInput();
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'parent'; // Default to parent

        if ($name === '' || $email === '' || $password === '') {
            Response::json(false, 'Name, email and password are required', null, 422);
        }

        $userModel = new UserModel($this->db);
        if ($userModel->findByEmail($email)) {
            Response::json(false, 'A user with this email already exists', null, 400);
        }

        $id = $userModel->create($name, $email, $password, $role);
        Response::json(true, 'register', ['id' => $id, 'message' => 'User registered successfully']);
    }

    public function login(): void
    {
        $input = Helper::getJsonInput();
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::json(false, 'Email and password are required', null, 422);
        }

        $userModel = new UserModel($this->db);
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, (string) $user['password'])) {
            Response::json(false, 'Invalid email or password', null, 401);
        }

        $secret = getenv('JWT_SECRET') ?: 'change_this_secret_key';
        $token = Helper::generateJWT([
            'user_id' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ], $secret, 60 * 60 * 24 * 7);

        unset($user['password']);

        Response::json(true, 'login', [
            'token' => $token,
            'role' => $user['role'],
            'user' => $user
        ]);
    }

    public function forgotPassword(): void
    {
        $input = Helper::getJsonInput();
        $email = trim($input['email'] ?? '');

        if ($email === '') {
            Response::json(false, 'Email is required', null, 422);
        }

        $userModel = new UserModel($this->db);
        $user = $userModel->findByEmail($email);
        
        if ($user) {
            $ttlMinutes = (int) (getenv('RESET_TOKEN_TTL_MINUTES') ?: 10);
            if ($ttlMinutes <= 0) {
                $ttlMinutes = 10;
            }

            $token = Helper::randomToken(64);
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$ttlMinutes} minutes"));

            // Keep only one active reset token per user.
            $userModel->invalidateActiveResetTokensForUser((int) $user['id']);
            $userModel->storeResetToken((int) $user['id'], $token, $expiresAt);

            $baseUrl = getenv('APP_URL') ?: 'http://localhost:8000';
            $resetLink = $baseUrl . '/reset-password.php?token=' . urlencode($token);

            $mailSent = Mailer::sendPasswordReset($email, $resetLink, $ttlMinutes);
            if (!$mailSent) {
                // Prevent storing dead reset tokens when email dispatch fails.
                $userModel->deleteResetTokenByValue($token);
                Response::json(false, 'Unable to send reset email right now. Please try again in a moment.', [
                    'provider' => Mailer::getLastProvider(),
                    'reason' => Mailer::getLastError(),
                ], 500);
            }

            Response::json(true, 'forgot-password', [
                'message' => 'Reset link sent immediately to your registered email',
                'provider' => Mailer::getLastProvider(),
                'timezone' => date_default_timezone_get(),
                'sent_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ]);
        } else {
            Response::json(false, 'Email not registered', null, 404);
        }
    }

    public function resetPassword(): void
    {
        $input = Helper::getJsonInput();
        $token = $input['token'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if ($token === '' || $newPassword === '') {
            Response::json(false, 'Token and new password are required', null, 422);
        }

        if (strlen($newPassword) < 6) {
            Response::json(false, 'New password must be at least 6 characters', null, 422);
        }

        $userModel = new UserModel($this->db);
        $reset = $userModel->getValidResetByToken($token);
        if (!$reset) {
            Response::json(false, 'Invalid or expired reset token', null, 400);
        }

        $this->db->beginTransaction();
        try {
            $userModel->updatePassword((int) $reset['user_id'], $newPassword);
            $userModel->markResetTokenUsed((int) $reset['reset_id']);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        Response::json(true, 'reset-password', 'Password reset successful');
    }

    public function updateToken(): void
    {
        $user = AuthMiddleware::authenticate();
        $input = Helper::getJsonInput();
        $token = $input['fcm_token'] ?? '';

        if ($token === '') {
            Response::json(false, 'fcm_token is required', null, 422);
        }

        $stmt = $this->db->prepare('UPDATE users SET fcm_token = :token WHERE id = :id');
        $stmt->execute([
            'token' => $token,
            'id' => (int) $user['user_id']
        ]);

        Response::json(true, 'update-token', 'Device token updated successfully');
    }

    public function logout(): void
    {
        AuthMiddleware::authenticate();
        // Since we use JWT, logout is usually handled client-side by deleting the token.
        // We can implement a blacklist here if needed.
        Response::json(true, 'logout', 'Logged out successfully');
    }
}
