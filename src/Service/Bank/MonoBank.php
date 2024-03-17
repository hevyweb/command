<?php

namespace App\Service\Bank;

use App\Exception\CurrencyNotFoundException;
use App\Exception\JsonDataInconsistentException;
use App\Exception\ServerResponseFailed;
use App\Service\Currency\CurrencyProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MonoBank implements BankInterface
{
    public const API_URL = 'https://api.monobank.ua/bank/currency';

    public const UAH_ISO = 980;
    private ?array $currencies = null;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CurrencyProviderInterface $currencyProvider,
    ) {
    }

    public function getExchangeRates(): array
    {
        return $this->parseData($this->loadData());
    }

    public function loadData(): array
    {
        $response = $this->client->request(Request::METHOD_GET, self::API_URL);

        if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new ServerResponseFailed('Monobank server sent invalid response.');
        }

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function parseData(array $input): array
    {
        $return = [];
        foreach ($input as $exchangeRate) {
            if (!isset($exchangeRate['currencyCodeB'])) {
                throw new JsonDataInconsistentException('Json does not contain currencyCodeB.');
            }

            if (self::UAH_ISO == $exchangeRate['currencyCodeB']) {
                $return[$this->convertCurrency($exchangeRate)] = $this->getRate($exchangeRate);
            }
        }

        return $return;
    }

    private function getRate(array $exchangeRate): float
    {
        if (isset($exchangeRate['rateCross'])) {
            return round($exchangeRate['rateCross'], 4);
        } elseif (isset($exchangeRate['rateSell']) && isset($exchangeRate['rateBuy'])) {
            return round(($exchangeRate['rateSell'] + $exchangeRate['rateBuy']) / 2, 4);
        }
        throw new JsonDataInconsistentException('Data is inconsistent. Not able to identify exchange rate.');
    }

    private function convertCurrency(array $exchangeRate): string
    {
        if (!isset($exchangeRate['currencyCodeA'])){
            throw new JsonDataInconsistentException('Json does not contain currencyCodeA.');
        }

        foreach ($this->getCurrencies() as $currency => $iso) {
            if ($iso['ISOnum'] == $exchangeRate['currencyCodeA']) {
                return $currency;
            }
        }

        throw new CurrencyNotFoundException($exchangeRate['currencyCodeA']);
    }

    private function getCurrencies(): array
    {
        if (!$this->currencies) {
            $this->currencies = $this->currencyProvider->getCurrencies();
        }

        return $this->currencies;
    }
}
