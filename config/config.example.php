<?php
/**
 * Configuración de la aplicación.
 *
 * Copia este archivo como `config/config.php` y ajusta los valores.
 * `config/config.php` no debe subirse al control de versiones.
 */

return [
    'app' => [
        'name'     => 'APEX Motorsport',
        // true muestra los errores en pantalla; en producción debe ser false.
        'debug'    => false,
        'timezone' => 'Europe/Madrid',
    ],

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'apex_motorsport',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    'uploads' => [
        // Directorio (relativo a public/) donde se guardan las fotos de los coches.
        'dir'       => 'assets/img/coches',
        'max_bytes' => 5 * 1024 * 1024,
    ],
];
