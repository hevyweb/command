<?php

namespace App\Service;

use App\Entity\ExchangeRate;
use App\Entity\Threshold;
use App\Event\ExchangeRateEvent;
use App\Service\Bank\BankInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class ExchangeRateWatcher
{
    public function __construct(
        private BankInterface $bank,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function watch(): void
    {
        $exchangeRates = $this->bank->getExchangeRates();
        $thresholds = $this->entityManager->getRepository(Threshold::class)->findAll();
        $existingExchangeRates = $this->entityManager->getRepository(ExchangeRate::class)->findExistingRates($thresholds);
        $event = new ExchangeRateEvent();
        foreach ($thresholds as $threshold) {
            $currency = $threshold->getCurrency();
            if (isset($exchangeRates[$currency])) {
                $newExRate = $exchangeRates[$currency];
                if (isset($existingExchangeRates[$currency])) {
                    /**
                     * @var ExchangeRate $oldExRate
                     */
                    $oldExRate = $existingExchangeRates[$currency];
                    if (abs($oldExRate->getRate() - $newExRate) > $threshold->getLevel()) {
                        $event->addViolation($newExRate, $oldExRate->getRate(), $currency);
                    }
                    $this->updateExistingExRate($oldExRate, $newExRate);
                } else {
                    $this->createNew($newExRate, $currency);
                }
            }
        }

        $this->entityManager->flush();

        if (count($event->getViolations())) {
            $this->dispatcher->dispatch($event);
        }
    }

    private function updateExistingExRate(ExchangeRate $exchangeRate, float $newExchangeRate): void
    {
        $exchangeRate
            ->setRate($newExchangeRate)
            ->setUpdateDate(new \DateTime());
    }

    private function createNew(float $rate, string $currency): void
    {
        $exchangeRate = new ExchangeRate();
        $exchangeRate
            ->setRate($rate)
            ->setCurrency($currency)
            ->setUpdateDate(new \DateTime());

        $this->entityManager->persist($exchangeRate);
    }
}
