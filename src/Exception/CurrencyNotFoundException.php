<?php

namespace App\Exception;

class CurrencyNotFoundException extends \Exception
{
    public function __construct(string $currency = "", int $code = 0, ?\Throwable $previous = null)
    {
        $message = 'Currency "' . $currency . '" not found.';
        parent::__construct($message, $code, $previous);
    }
}