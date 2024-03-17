<?php

namespace App\tests\Unit;

use App\Entity\ExchangeRate;
use App\Entity\Threshold;
use App\Event\ExchangeRateEvent;
use App\Repository\ExchangeRateRepository;
use App\Service\Bank\BankInterface;
use App\Service\ExchangeRateWatcher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExchangeRateWatcherTest extends TestCase
{
    private ExchangeRateWatcher $sut;

    private BankInterface|MockObject $bank;

    private EntityManagerInterface|MockObject $entityManager;

    private EventDispatcherInterface|MockObject $dispatcher;

    public function setUp(): void
    {
        $this->bank = $this->createMock(BankInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->sut = new ExchangeRateWatcher(
            $this->bank,
            $this->entityManager,
            $this->dispatcher,
        );
    }

    public function testWatch()
    {
        $exchangeRateRepository = $this->createMock(ExchangeRateRepository::class);
        $thresholdRepository = $this->createMock(ServiceEntityRepository::class);

        $this->bank->method('getExchangeRates')->willReturn($this->exchangeRateMock());
        $this->entityManager->method('getRepository')->willReturnOnConsecutiveCalls(
            $thresholdRepository,
            $exchangeRateRepository,
        );

        $thresholdRepository->method('findAll')->willReturn($this->thresholdMock());
        $exchangeRateRepository->method('findExistingRates')->willReturn($this->existingExchangeRateMock());

        $this->entityManager->expects($this->once())->method('persist');

        $expectedEvent = (new ExchangeRateEvent())->addViolation(38.1234, 33.1232, 'USD');
        $this->dispatcher->expects($this->once())->method('dispatch')->with($expectedEvent);
        $this->sut->watch();
    }

    private function exchangeRateMock(): array
    {
        return [
            'USD' => 38.1234,
            'EUR' => 45.4568,
            'BRL' => 125.66,
            'CNY' => 1.3222,
        ];
    }

    private function thresholdMock(): array
    {
        $threshold = $this->createMock(Threshold::class);
        $threshold->method('getCurrency')->willReturnOnConsecutiveCalls('USD', 'BRL', 'CNY');
        $threshold->method('getLevel')->willReturnOnConsecutiveCalls(5.0001, 1, 0.0001);
        return [
            $threshold,
            $threshold,
            $threshold,
        ];
    }

    private function existingExchangeRateMock(): array
    {
        $exchangeRateUSD = $this->createMock(ExchangeRate::class);
        $exchangeRateUSD->method('getRate')->willReturn(33.1232);
        $exchangeRateUSD->expects($this->once())->method('setRate')->with(38.1234);

        $exchangeRateBRL = $this->createMock(ExchangeRate::class);
        $exchangeRateBRL->method('getRate')->willReturn(125.65);
        $exchangeRateBRL->expects($this->once())->method('setRate')->with(125.66);

        return [
            'USD' => $exchangeRateUSD,
            'BRL' => $exchangeRateBRL,
        ];
    }
}