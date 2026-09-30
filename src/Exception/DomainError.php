<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Error de negocio cuyo mensaje es seguro para mostrárselo al usuario
 * (p. ej. "No quedan unidades en stock").
 */
class DomainError extends \RuntimeException
{
}
