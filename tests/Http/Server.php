<?php

declare(strict_types=1);

namespace Tests\Http;

/**
 * Arranca el servidor embebido de PHP sobre public/ con la configuración de test.
 */
final class Server
{
    /** @var resource|null */
    private static $process = null;
    private static string $configFile = '';
    private static int $port = 0;

    public static function start(): string
    {
        if (self::$process !== null) {
            return self::url();
        }

        $tmp = tempnam(sys_get_temp_dir(), 'apex-config');
        unlink($tmp);
        self::$configFile = $tmp . '.php';
        file_put_contents(self::$configFile, '<?php return ' . var_export(TEST_CONFIG, true) . ';');

        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $cmd = [PHP_BINARY, '-S', '127.0.0.1:' . self::$port, '-t', PUBLIC_PATH];
        $env = array_merge(getenv(), ['APEX_CONFIG' => self::$configFile]);
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        self::$process = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes, null, $env);

        for ($i = 0; $i < 50; $i++) {
            if (@fsockopen('127.0.0.1', self::$port)) {
                return self::url();
            }
            usleep(100_000);
        }
        throw new \RuntimeException('No se pudo arrancar el servidor de pruebas.');
    }

    public static function stop(): void
    {
        if (self::$process !== null) {
            proc_terminate(self::$process);
            proc_close(self::$process);
            self::$process = null;
            @unlink(self::$configFile);
        }
    }

    private static function url(): string
    {
        return 'http://127.0.0.1:' . self::$port . '/';
    }
}
