<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Conexión PDO única por petición.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                Config::get('db.host', '127.0.0.1'),
                (int) Config::get('db.port', 3306),
                Config::get('db.name'),
                Config::get('db.charset', 'utf8mb4')
            );

            self::$connection = new PDO($dsn, Config::get('db.user'), Config::get('db.password'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Misma zona horaria que PHP, para que CURRENT_TIMESTAMP y date() coincidan.
            self::$connection->exec("SET time_zone = '" . date('P') . "'");
        }

        return self::$connection;
    }
}
