<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStock extends RuntimeException
{
    public static function for(string $sku, string $bin, int $onHand, int $delta): self
    {
        return new self("Cannot move {$delta} of {$sku} in bin {$bin}: only {$onHand} on hand.");
    }
}
