<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connect(array $config): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dbPath = $config['database'] ?? base_path('database/posg.sqlite');

        try {
            self::$connection = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Enable SQLite Foreign Key constraints
            self::$connection->exec('PRAGMA foreign_keys = ON;');

            return self::$connection;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$connection) {
            throw new PDOException('Database is not connected.');
        }

        return self::$connection;
    }
}
