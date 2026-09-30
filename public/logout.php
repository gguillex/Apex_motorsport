<?php
/**
 * POST logout.php — Cierra la sesión.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Auth;
use App\Csrf;

if (is_post()) {
    Csrf::verify();
    Auth::logout();
}

redirect('index.php');
