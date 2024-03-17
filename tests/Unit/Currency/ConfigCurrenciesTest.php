<?php

namespace App\tests\Unit\Currency;

use App\Exception\NotAbleToReadFileException;
use App\Service\Currency\ConfigCurrencies;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigCurrenciesTest extends TestCase
{
    private ConfigCurrencies $sut;

    private ParameterBagInterface|MockObject $parameterBag;

    public function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->sut = new ConfigCurrencies($this->parameterBag);
    }

    public function testFailToReadFromFile(): void
    {
        $this->parameterBag->method('get')->willReturnOnConsecutiveCalls('dummy', '/data');
        $this->expectException(NotAbleToReadFileException::class);

        @$this->sut->getCurrencies();
    }

    public function testFailToParseJson(): void
    {
        $this->parameterBag->method('get')->willReturnOnConsecutiveCalls(__DIR__, '/ConfigCurrenciesTest.php');
        $this->expectException(\JsonException::class);

        $this->sut->getCurrencies();
    }

    public function testGetCurrencies(): void
    {
        $this->parameterBag->method('get')->willReturnOnConsecutiveCalls(
            __DIR__.'/../../../config/', 'currencies.json');

        $result = $this->sut->getCurrencies();
        $expected = [
                'name' => 'United States Dollar',
                'demonym' => 'US',
                'majorSingle' => 'Dollar',
                'majorPlural' => 'Dollars',
                'ISOnum' => 840,
                'symbol' => '$',
                'symbolNative' => '$',
                'minorSingle' => 'Cent',
                'minorPlural' => 'Cents',
                'ISOdigits' => 2,
                'decimals' => 2,
                'numToBasic' => 100,
        ];

        $this->assertEquals($expected, $result['USD']);
    }
}
