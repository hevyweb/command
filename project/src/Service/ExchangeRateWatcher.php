<?php

namespace App\Service;

use App\Service\Bank\BankInterface;

readonly class ExchangeRateWatcher
{
    public function __construct(
        private BankInterface $bank
    ) {
    }

    public function watch(): void
    {
        $exchangeRates = $this->bank->getExchangeRates();
    }
}