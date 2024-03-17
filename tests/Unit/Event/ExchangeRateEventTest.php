<?php

namespace App\tests\Unit\Event;

use App\Event\ExchangeRateEvent;
use PHPUnit\Framework\TestCase;

class ExchangeRateEventTest extends TestCase
{
    public function testAddViolation(): void
    {
        $sut = new ExchangeRateEvent();

        $sut->addViolation(12.3456, 15.6789, 'USD');
        $sut->addViolation(11111, 22222, 'EUR');

        $expectedResult = [
            'USD' => [
                'new' => 12.3456,
                'old' => 15.6789,
            ],
            'EUR' => [
                'new' => 11111,
                'old' => 22222,
            ],
        ];
        $this->assertEquals($expectedResult, $sut->getViolations());
    }
}
