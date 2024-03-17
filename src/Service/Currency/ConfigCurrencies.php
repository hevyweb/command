<?php

namespace App\Service\Currency;

use App\Exception\NotAbleToReadFileException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class ConfigCurrencies implements CurrencyProviderInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function getCurrencies(): array
    {
        return $this->parseConfig($this->readConfigFile());
    }

    /**
     * @throws NotAbleToReadFileException
     */
    private function readConfigFile(): string
    {
        $filePath = $this->parameterBag->get('kernel.project_dir').$this->parameterBag->get('iso4217_file');;
        $content = file_get_contents($filePath);

        if (!$content) {
            throw new NotAbleToReadFileException($filePath);
        }
        return $content;
    }

    /**
     * @throws \JsonException
     */
    private function parseConfig(string $config): array
    {
        return json_decode($config, true, 512, JSON_THROW_ON_ERROR);
    }
}
