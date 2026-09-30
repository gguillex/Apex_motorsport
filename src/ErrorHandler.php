<?php

declare(strict_types=1);

namespace App;

use Throwable;

/**
 * Convierte avisos de PHP en excepciones y muestra una página de error
 * genérica sin filtrar detalles internos (salvo en modo debug).
 */
final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e) use ($debug): void {
            error_log((string) $e);

            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, (string) $e . PHP_EOL);
                exit(1);
            }

            $detail = $debug ? $e->getMessage() . "\n\n" . $e->getTraceAsString() : null;
            render_error(500, 'Se ha producido un error inesperado. Inténtalo de nuevo más tarde.', $detail);
        });
    }
}
