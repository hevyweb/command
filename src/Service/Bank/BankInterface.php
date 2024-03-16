<?php

namespace App\Service\Bank;

interface BankInterface
{
    public function getExchangeRates(): array;
}