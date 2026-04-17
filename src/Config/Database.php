<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
    private string $host;
    private string $port;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    public function __construct()
    {
        // 1. First, check if Railway's unified URL variables exist
        $url = $this->getEnvVar('MYSQL_URL', $this->getEnvVar('DATABASE_URL', ''));
        
        if (!empty($url)) {
            $url = trim($url);
            // Robust regex to parse mysql://user:pass@host:port/dbname
            if (preg_match('/^mysql:\/\/([^:]+):([^@]+)@([^:\/]+)(?::(\d+))?\/([^\?]+)/', $url, $matches)) {
                $this->username = $matches[1];
                $this->password = $matches[2];
                $this->host = $matches[3];
                $this->port = (string) ($matches[4] ?? '3306');
                $this->dbName = $matches[5];
                $this->charset = 'utf8mb4';
            } else {
                // Last ditch effort: if Regex fails, try parse_url but with scheme check
                if (!str_contains($url, '://')) { $url = 'mysql://' . $url; }
                $parsed = parse_url($url);
                $this->host = $parsed['host'] ?? '127.0.0.1';
                $this->port = (string) ($parsed['port'] ?? '3306');
                $this->dbName = ltrim($parsed['path'] ?? '/kindergarten_db', '/');
                $this->username = $parsed['user'] ?? 'root';
                $this->password = $parsed['pass'] ?? '';
                $this->charset = 'utf8mb4';
            }
        } else {
            // 2. Fallback to individual variables if no unified URL
            $this->host = $this->getEnvVar('DB_HOST', $this->getEnvVar('MYSQLHOST', '127.0.0.1'));
            $this->port = $this->getEnvVar('DB_PORT', $this->getEnvVar('MYSQLPORT', '3306'));
            $this->dbName = $this->getEnvVar('DB_NAME', $this->getEnvVar('MYSQLDATABASE', 'kindergarten_db'));
            $this->username = $this->getEnvVar('DB_USER', $this->getEnvVar('MYSQLUSER', 'root'));
            $this->password = $this->getEnvVar('DB_PASS', $this->getEnvVar('MYSQLPASSWORD', ''));
            $this->charset = $this->getEnvVar('DB_CHARSET', 'utf8mb4');
        }

        // Prevent PDO from using Unix socket if host is 'localhost'
        if ($this->host === 'localhost') {
            $this->host = '127.0.0.1';
        }
    }

    private function getEnvVar(string $key, string $default): string
    {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return (string) $val;
    }

    public function connect(): PDO
    {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            // Return JSON error response if DB connection fails
            \App\Utils\Response::json(false, 'Database Connection Failed', [
                'error' => 'Could not connect to the database server.',
                'detail' => $e->getMessage()
            ], 500);
            exit;
        }
    }
}
