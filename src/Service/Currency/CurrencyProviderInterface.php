<?php

namespace App\Service\Currency;

interface CurrencyProviderInterface
{
    public function getCurrencies(): array;
}
