<?php

declare(strict_types=1);

namespace App\Utils;

class Helper
{
    public static function getJsonInput(): array
    {
        // 1. Try to get JSON from the request body
        $raw = file_get_contents('php://input');
        if ($raw !== false && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // 2. Fallback to standard POST data (for Postman form-data)
        return $_POST;
    }

    public static function getBearerToken(): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($headers['Authorization'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        return trim(substr((string) $authHeader, 7));
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    public static function generateJWT(array $payload, string $secret, int $ttlSeconds = 86400): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $encodedHeader = self::base64UrlEncode((string) json_encode($header));
        $encodedPayload = self::base64UrlEncode((string) json_encode($payload));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true);
        $encodedSignature = self::base64UrlEncode($signature);

        return $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
    }

    public static function verifyJWT(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $expected = self::base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true));
        if (!hash_equals($expected, $encodedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!is_array($payload)) {
            return null;
        }
        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    public static function randomToken(int $length = 64): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    public static function loadEnvFile(string $path): void
    {
        // Try config.env first, then .env as fallback
        if (!file_exists($path)) {
            $path = str_replace('config.env', '.env', $path);
        }

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip quotes if present
            if (
                ($value !== '') &&
                (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                 ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                // Do not override runtime environment variables (e.g. Railway secrets).
                // This keeps production config authoritative while still supporting local files.
                $existing = getenv($key);
                if ($existing !== false && trim((string) $existing) !== '') {
                    continue;
                }

                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        // Ensure a consistent app timezone for all date()/strtotime() operations.
        $tz = (string) (getenv('APP_TIMEZONE') ?: getenv('TZ') ?: 'Asia/Kolkata');
        if (!in_array($tz, timezone_identifiers_list(), true)) {
            $tz = 'Asia/Kolkata';
        }
        date_default_timezone_set($tz);
    }

    public static function uploadFile(array $file, string $targetDir, array $allowedMimes = [], int $maxSize = 5242880): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > $maxSize) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!empty($allowedMimes) && !in_array($mime, $allowedMimes, true)) {
            return null;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = self::randomToken(16) . '.' . $extension;
        $targetPath = rtrim($targetDir, '/') . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $filename;
        }

        return null;
    }
}
