<?php
/**
 * Punto de arranque común a todas las páginas de public/.
 *
 * Carga la configuración, registra el autoloader, configura el manejo de
 * errores, arranca la sesión y envía las cabeceras de seguridad.
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');

// Autoloader PSR-4 mínimo: App\Foo\Bar -> src/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';

// La variable de entorno APEX_CONFIG permite usar otra configuración (p. ej. en los tests).
$configFile = getenv('APEX_CONFIG') ?: ROOT_PATH . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = ROOT_PATH . '/config/config.example.php';
}
App\Config::load(require $configFile);

date_default_timezone_set((string) config('app.timezone', 'Europe/Madrid'));
mb_internal_encoding('UTF-8');
ini_set('serialize_precision', '-1'); // JSON sin ruido en los decimales (3.7 y no 3.7000000000000002)

App\ErrorHandler::register((bool) config('app.debug', false));

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'use_strict_mode' => true,
    ]);
}
