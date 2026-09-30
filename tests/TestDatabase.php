<?php

declare(strict_types=1);

namespace Tests;

use App\Config;
use PDO;

/**
 * Crea y reinicia una base de datos exclusiva para los tests
 * (<nombre configurado>_test), a partir de database/schema.sql.
 * Nunca toca la base de datos real.
 */
final class TestDatabase
{
    /** Nombre de la BD de test (debe llamarse tras configure()). */
    public static function name(): string
    {
        return (string) Config::get('db.name');
    }

    /** Devuelve una copia de la configuración que apunta a la BD de test. */
    public static function configure(array $config): array
    {
        $config['db']['name'] .= '_test';
        return $config;
    }

    public static function isAvailable(): bool
    {
        try {
            self::server();
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    private static function server(): PDO
    {
        return new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', Config::get('db.host'), (int) Config::get('db.port')),
            Config::get('db.user'),
            Config::get('db.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function reset(): void
    {
        $pdo = self::server();
        $sql = file_get_contents(ROOT_PATH . '/database/schema.sql');
        // El esquema usa el nombre "apex_motorsport"; lo sustituimos por el de test.
        $sql = str_replace('`apex_motorsport`', '`' . self::name() . '`', $sql);

        foreach (self::statements($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    /** @return list<string> */
    private static function statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        return array_values(array_filter(array_map('trim', explode(";\n", $sql))));
    }
}
