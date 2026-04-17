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
        $this->host = $this->getEnvVar('DB_HOST', '127.0.0.1');
        $this->port = $this->getEnvVar('DB_PORT', '3306');
        $this->dbName = $this->getEnvVar('DB_NAME', 'kindergarten_db');
        $this->username = $this->getEnvVar('DB_USER', 'root');
        $this->password = $this->getEnvVar('DB_PASS', '');
        $this->charset = $this->getEnvVar('DB_CHARSET', 'utf8mb4');
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
