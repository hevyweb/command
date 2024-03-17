<?php

namespace App\Service\Bank;

interface BankInterface
{
    public function getExchangeRates(): array;

    public function parseData(array $input): array;
}
