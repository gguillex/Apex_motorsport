<?php

declare(strict_types=1);

namespace App\Exception;

final class OutOfStockException extends DomainError
{
    public function __construct()
    {
        parent::__construct('Lo sentimos, este vehículo se ha agotado y no se ha podido completar la compra.');
    }
}
