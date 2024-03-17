<?php

namespace App\tests\Unit\Service\Bank;

use App\Exception\CurrencyNotFoundException;
use App\Exception\JsonDataInconsistentException;
use App\Exception\ServerResponseFailed;
use App\Service\Bank\MonoBank;
use App\Service\Currency\CurrencyProviderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MonoBankTest extends TestCase
{
    private MonoBank $sut;

    private HttpClientInterface|MockObject $client;

    private CurrencyProviderInterface|MockObject $currencyProvider;

    public function setUp(): void
    {

        $this->client = $this->createMock(HttpClientInterface::class);

        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->currencyProvider->method('getCurrencies')->willReturn($this->currencyMock());

        $this->sut = new MonoBank(
            $this->client,
            $this->currencyProvider
        );
    }
    
    private function currencyMock(): array
    {
        return [
            'USD'=> [
                'name'=> 'United States Dollar',
                'demonym'=> 'US',
                'majorSingle'=> 'Dollar',
                'majorPlural'=> 'Dollars',
                'ISOnum'=> 840,
                'symbol'=> '$',
                'symbolNative'=> '$',
                'minorSingle'=> 'Cent',
                'minorPlural'=> 'Cents',
                'ISOdigits'=> 2,
                'decimals'=> 2,
                'numToBasic'=> 100,
            ],
            'EUR' => [
                'name'=> 'Euro',
                'demonym'=> '',
                'majorSingle'=> 'Euro',
                'majorPlural'=> 'Euros',
                'ISOnum'=> 978,
                'symbol'=> '€',
                'symbolNative'=> '€',
                'minorSingle'=> 'Cent',
                'minorPlural'=> 'Cents',
                'ISOdigits'=> 2,
                'decimals'=> 2,
                'numToBasic'=> 100
	        ],
        ];
    }

    public function testLoadDataError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(400);

        $this->client->method('request')->willReturn($response);

        $this->expectException(ServerResponseFailed::class);
        $this->sut->getExchangeRates();
    }

    public function testResponseInvalidJson(): void
    {
        $invalidJson = "<div>This is invalid json string</div>";

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($invalidJson);

        $this->client->method('request')->willReturn($response);

        $this->expectException(\JsonException::class);
        $this->sut->getExchangeRates();
    }

    public function testNoExchangeRatesInResponse(): void
    {
        $json = json_encode([
            [
                "currencyCodeA" => 978,
                "currencyCodeB" => $this->sut::UAH_ISO,
                "date" => 1710572406,
            ],
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($json);

        $this->client->method('request')->willReturn($response);

        $this->expectException(JsonDataInconsistentException::class);
        $this->expectExceptionMessage('Data is inconsistent. Not able to identify exchange rate.');
        $this->sut->getExchangeRates();
    }

    public function testCurrencyNotFound(): void
    {
        $json = json_encode([
            [
                "currencyCodeA" => 48,
                "currencyCodeB" => $this->sut::UAH_ISO,
                "date" => 1710572406,
                "rateBuy" => 42.05,
                "rateSell" => 42.6003,
            ],
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($json);

        $this->client->method('request')->willReturn($response);

        $this->expectException(CurrencyNotFoundException::class);
        $this->sut->getExchangeRates();
    }

    #[DataProvider('invalidCurrencyAttributeProvider')]
    public function testCurrencyCodeMissing(string $json, string $errorMessage): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($json);

        $this->client->method('request')->willReturn($response);

        $this->expectException(JsonDataInconsistentException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->sut->getExchangeRates();
    }

    public static function invalidCurrencyAttributeProvider(): \Iterator
    {
        yield 'No currencyCodeA' => [
            json_encode([
                [
                    "currencyCodeB" => 980,
                    "date" => 1710572406,
                    "rateBuy" => 42.05,
                    "rateSell" => 42.6003,
                ],
            ]),
            'Json does not contain currencyCodeA.'
        ];

        yield 'No currencyCodeB' => [
            json_encode([
                [
                    "currencyCodeA" => 48,
                    "date" => 1710572406,
                    "rateBuy" => 42.05,
                    "rateSell" => 42.6003,
                ],
            ]),
            'Json does not contain currencyCodeB.'
        ];
    }

    public function testGetExchangeRates(): void
    {
        $json = json_encode([
            [
                "currencyCodeA" => 840,
                "currencyCodeB" => $this->sut::UAH_ISO,
                "date" => 1710572406,
                "rateBuy" => 46.4007,
                "rateSell" => 42.6003,
            ],
            [
                "currencyCodeA" => 978,
                "currencyCodeB" => $this->sut::UAH_ISO,
                "date" => 1710572406,
                "rateCross" => 49.5689,
            ],
        ]);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getContent')->willReturn($json);

        $this->client->method('request')->willReturn($response);

        $expected = [
            'USD' => 44.5005,
            'EUR' => 49.5689,
        ];

        $this->assertEquals($expected, $this->sut->getExchangeRates());
    }
}