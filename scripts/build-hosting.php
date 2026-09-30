<?php
/**
 * Genera un paquete listo para subir a un hosting compartido (InfinityFree,
 * Hostinger, cPanel…), donde no se puede elegir la carpeta raíz ni crear bases
 * de datos por SQL.
 *
 * Uso:
 *   php scripts/build-hosting.php [opciones]
 *
 * Opciones (todas opcionales):
 *   --admin-password=CLAVE   Contraseña del usuario "admin" en el hosting.
 *                            Muy recomendable: la de serie (admin123) es pública.
 *   --db-host=HOST           Servidor MySQL del hosting (p. ej. sql123.infinityfree.com)
 *   --db-name=NOMBRE         Nombre de la base de datos (p. ej. if0_12345678_apex)
 *   --db-user=USUARIO        Usuario MySQL (p. ej. if0_12345678)
 *   --db-password=CLAVE      Contraseña MySQL
 *
 * Resultado (carpeta dist/):
 *   apex-motorsport-hosting.zip   Contenido de htdocs/ (subir y descomprimir).
 *   instalar.sql                  Importar desde phpMyAdmin en la BD del hosting.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Este script se ejecuta desde la línea de comandos.');
}
if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Falta la extensión zip de PHP.\n");
    exit(1);
}

$root = dirname(__DIR__);
$dist = $root . '/dist';
$options = getopt('', ['admin-password:', 'db-host:', 'db-name:', 'db-user:', 'db-password:']);

/* ---------- 1. SQL de instalación ---------- */

$sql = file_get_contents($root . '/database/schema.sql');

// En hosting compartido la base de datos se crea desde el panel: se eliminan
// CREATE DATABASE y USE para importar directamente en la BD seleccionada.
$sql = preg_replace('/^CREATE DATABASE[^;]+;\s*$/mi', '', $sql);
$sql = preg_replace('/^USE `[^`]+`;\s*$/mi', '', $sql);

if (!empty($options['admin-password'])) {
    if (strlen($options['admin-password']) < 8) {
        fwrite(STDERR, "La contraseña de admin debe tener al menos 8 caracteres.\n");
        exit(1);
    }
    $hash = password_hash($options['admin-password'], PASSWORD_DEFAULT);
    // Se usa un callback porque el hash contiene "$" y en una cadena de
    // sustitución se interpretaría como referencia ($2, $10…).
    $sql = preg_replace_callback(
        "/\('admin', '\\$2y\\$[^']+', 'admin'\)/",
        static fn (): string => "('admin', '$hash', 'admin')",
        $sql,
        1,
        $count
    );
    // Comprobación: el hash que queda en el SQL debe validar la contraseña.
    preg_match("/\('admin', '([^']+)', 'admin'\)/", $sql, $m);
    if ($count !== 1 || !password_verify($options['admin-password'], $m[1] ?? '')) {
        fwrite(STDERR, "Error: no se pudo establecer la contraseña de admin en el SQL.\n");
        exit(1);
    }
    $adminNote = 'contraseña personalizada';
} else {
    $adminNote = "admin123 (¡CÁMBIALA en cuanto entres!)";
}

$header = "-- APEX Motorsport — instalación en hosting compartido\n"
        . "-- Generado el " . date('d/m/Y H:i') . " con scripts/build-hosting.php\n"
        . "-- Importar desde phpMyAdmin con la base de datos del hosting seleccionada.\n\n";

/* ---------- 2. Configuración ---------- */

$config = file_get_contents($root . '/config/config.example.php');
$replacements = [
    'db-host'     => "'host'     => '127.0.0.1'",
    'db-name'     => "'name'     => 'apex_motorsport'",
    'db-user'     => "'user'     => 'root'",
    'db-password' => "'password' => ''",
];
$placeholders = [
    'db-host'     => 'SERVIDOR_MYSQL',
    'db-name'     => 'NOMBRE_BD',
    'db-user'     => 'USUARIO_MYSQL',
    'db-password' => 'CONTRASEÑA_MYSQL',
];
$pending = [];
foreach ($replacements as $option => $search) {
    $key = explode("'", $search)[1];
    $value = $options[$option] ?? $placeholders[$option];
    if (!isset($options[$option])) {
        $pending[] = $key;
    }
    $config = str_replace($search, sprintf('%s => %s', str_pad("'$key'", 10), var_export($value, true)), $config);
}

/* ---------- 3. Paquete ---------- */

if (!is_dir($dist)) {
    mkdir($dist, 0775, true);
}
$zipPath = $dist . '/apex-motorsport-hosting.zip';
@unlink($zipPath);

$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::CREATE);

// Carpetas y archivos que se publican. Tests, documentación y CI se quedan fuera.
$include = ['.htaccess', 'index.php', 'public', 'src', 'templates', 'config/.htaccess', 'config/config.example.php', 'database/.htaccess'];
$files = 0;

foreach ($include as $entry) {
    $path = $root . '/' . $entry;
    if (is_file($path)) {
        $zip->addFile($path, $entry);
        $files++;
        continue;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1);
        // Las fotos subidas en local no se publican.
        if (str_contains($relative, '/upload-')) {
            continue;
        }
        $zip->addFile($file->getPathname(), $relative);
        $files++;
    }
}

// Comprobación de seguridad: la configuración generada debe ser PHP válido.
$tmp = tempnam(sys_get_temp_dir(), 'cfg');
file_put_contents($tmp, $config);
$generated = include $tmp;
unlink($tmp);
if (!is_array($generated) || !isset($generated['db']['host'])) {
    fwrite(STDERR, "Error interno: la configuración generada no es válida.\n");
    exit(1);
}

$zip->addFromString('config/config.php', $config);
$zip->addFromString('database/instalar.sql', $header . ltrim($sql));
$zip->close();

file_put_contents($dist . '/instalar.sql', $header . ltrim($sql));

/* ---------- 4. Resumen ---------- */

printf("Paquete creado: %s (%d archivos, %s KB)\n", $zipPath, $files + 2, number_format(filesize($zipPath) / 1024, 0, ',', '.'));
printf("SQL de instalación: %s\n", $dist . '/instalar.sql');
printf("Usuario admin: %s\n", $adminNote);
if ($pending) {
    printf("\nPendiente: edita config/config.php en el hosting y completa: %s\n", implode(', ', $pending));
}
