<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ExchangeRateEvent extends Event
{
    private array $violations = [];

    public function addViolation(float $newExRate, float $oldExRate, string $currency): self
    {
        $this->violations[$currency] = [
            'new' => $newExRate,
            'old' => $oldExRate,
        ];

        return $this;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
