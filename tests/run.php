<?php
/**
 * Ejecuta todos los tests:   php tests/run.php [filtro]
 *
 * Requiere un servidor MySQL/MariaDB accesible con las credenciales de
 * config/config.php (o del archivo indicado en la variable APEX_CONFIG).
 * Los tests crean y usan su propia base de datos "<nombre>_test".
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/TestCase.php';
require __DIR__ . '/TestDatabase.php';

use App\Config;
use Tests\AssertionFailed;
use Tests\TestDatabase;

$configFile = getenv('APEX_CONFIG') ?: ROOT_PATH . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = ROOT_PATH . '/config/config.example.php';
}
$testConfig = TestDatabase::configure(require $configFile);
$testConfig['app']['debug'] = true;
Config::load($testConfig);
define('TEST_CONFIG', $testConfig);

$filter = $argv[1] ?? '';
$files = glob(__DIR__ . '/Unit/*Test.php');

if (TestDatabase::isAvailable()) {
    $files = array_merge($files, glob(__DIR__ . '/Integration/*Test.php'), glob(__DIR__ . '/Http/*Test.php'));
} else {
    fwrite(STDERR, sprintf(
        "AVISO: no se puede conectar a MySQL en %s:%d. Solo se ejecutarán los tests unitarios.\n" .
        "       Arranca MySQL (p. ej. desde el panel de XAMPP) o revisa config/config.php.\n",
        Config::get('db.host'),
        (int) Config::get('db.port')
    ));
}

$useColor = stream_isatty(STDOUT);
$paint = static fn (string $text, string $code) => $useColor ? "\033[{$code}m{$text}\033[0m" : $text;

$passed = $failed = $assertions = 0;
$failures = [];
$start = microtime(true);

foreach ($files as $file) {
    require_once $file;
    $class = 'Tests\\' . basename(dirname($file)) . '\\' . basename($file, '.php');
    if ($filter !== '' && stripos($class, $filter) === false) {
        continue;
    }

    echo PHP_EOL . $paint($class, '1') . PHP_EOL;
    $class::setUpBeforeClass();

    foreach (get_class_methods($class) as $method) {
        if (!str_starts_with($method, 'test')) {
            continue;
        }

        $test = new $class();
        $label = ltrim(strtolower(preg_replace('/[A-Z]/', ' $0', substr($method, 4))));

        try {
            $test->setUp();
            $test->$method();
            $test->tearDown();
            $passed++;
            echo '  ' . $paint('✔', '32') . " $label" . PHP_EOL;
        } catch (Throwable $e) {
            $failed++;
            $kind = $e instanceof AssertionFailed ? '' : get_class($e) . ': ';
            $failures[] = "$class::$method\n    " . str_replace("\n", "\n    ", $kind . $e->getMessage())
                . ($e instanceof AssertionFailed ? '' : "\n    en " . $e->getFile() . ':' . $e->getLine());
            echo '  ' . $paint('✘', '31') . " $label" . PHP_EOL;
            try {
                $test->tearDown();
            } catch (Throwable) {
            }
        }
        $assertions += $test->assertions;
    }

    $class::tearDownAfterClass();
}

$time = number_format(microtime(true) - $start, 2);

if ($failures) {
    echo PHP_EOL . $paint('Fallos:', '31;1') . PHP_EOL;
    foreach ($failures as $i => $failure) {
        echo PHP_EOL . ($i + 1) . ") $failure" . PHP_EOL;
    }
}

$summary = sprintf('%d tests, %d aserciones, %d fallos (%ss)', $passed + $failed, $assertions, $failed, $time);
echo PHP_EOL . $paint($summary, $failed ? '41;37' : '42;30') . PHP_EOL;
exit($failed ? 1 : 0);
