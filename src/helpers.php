<?php
/**
 * Funciones auxiliares globales usadas por páginas y plantillas.
 */

declare(strict_types=1);

use App\Config;
use App\Database;

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function db(): PDO
{
    return Database::connection();
}

/** Escapa texto para insertarlo de forma segura en HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ruta URL de la carpeta public/ (termina en "/"). Se calcula a partir del
 * script en ejecución, así la aplicación funciona tanto en la raíz del
 * dominio como en una subcarpeta (p. ej. http://localhost/Apex_motorsport/).
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $public = str_replace('\\', '/', (string) realpath(PUBLIC_PATH));
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($script !== '' && str_starts_with($script, $public . '/')) {
        $relative = substr($script, strlen($public) + 1);
        if (str_ends_with($scriptName, $relative)) {
            $base = substr($scriptName, 0, -strlen($relative));

            // Si la petición llegó a través del .htaccess de la raíz (la URL no
            // contiene "public/"), se generan enlaces limpios sin ese segmento.
            $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            if (str_ends_with($base, '/public/') && !str_starts_with($requestPath, $base)) {
                $base = substr($base, 0, -strlen('public/'));
            }
            return $base;
        }
    }

    return $base = '/';
}

function url(string $path = ''): string
{
    return base_url() . ltrim($path, '/');
}

/** URL de un recurso estático con parámetro de versión para invalidar la caché. */
function asset(string $path): string
{
    $file = PUBLIC_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $version;
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Lee un entero positivo de la query string (0 si no es válido). */
function query_id(string $key = 'id'): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value ?: 0;
}

/** Lee un entero positivo del cuerpo POST (0 si no es válido). */
function post_id(string $key = 'id'): int
{
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value ?: 0;
}

/** Guarda un mensaje para mostrarlo en la siguiente página (patrón PRG). */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** @return list<array{type:string, message:string}> */
function pull_flashes(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/** Incluye una plantilla de templates/ con las variables indicadas. */
function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require TEMPLATES_PATH . '/' . $template . '.php';
}

function render_error(int $status, string $message, ?string $detail = null): never
{
    if (!headers_sent()) {
        http_response_code($status);
    }
    view('error', ['status' => $status, 'message' => $message, 'detail' => $detail]);
    exit;
}

function format_price(float|int|string $amount): string
{
    $amount = (float) $amount;
    $decimals = floor($amount) == $amount ? 0 : 2;
    return number_format($amount, $decimals, ',', '.') . "\u{00A0}€";
}

function format_datetime(string $datetime): string
{
    return (new DateTimeImmutable($datetime))->format('d/m/Y H:i');
}

function car_name(array $car): string
{
    return trim($car['marca'] . ' ' . $car['modelo']);
}

/** Mensaje de error de validación de un campo (vacío si no hay error). */
function field_error(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<span class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</span>'
        : '';
}

/** Atributos ARIA para un campo con posible error de validación. */
function field_attrs(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';
}

/** Lee un campo de texto de un array de entrada ($_POST, $_GET…); '' si falta o no es escalar. */
function input_string(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_scalar($value) ? (string) $value : '';
}
