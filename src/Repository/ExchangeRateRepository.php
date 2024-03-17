<?php

namespace App\Repository;

use App\Entity\ExchangeRate;
use App\Entity\Threshold;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRate>
 *
 * @method ExchangeRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExchangeRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExchangeRate[]    findAll()
 * @method ExchangeRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function findExistingRates(array $thresholds): array
    {
        $currencies = array_map(fn (Threshold $threshold) => $threshold->getCurrency(), $thresholds);

        $existingExchangeRates = $this->findBy(['currency' => $currencies]);

        $return = [];
        foreach ($existingExchangeRates as $exchangeRate) {
            $return[$exchangeRate->getCurrency()] = $exchangeRate;
        }

        return $return;
    }
}
