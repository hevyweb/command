<?php

namespace App\Command;

use App\Entity\Threshold;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:threshold-upsert',
    description: 'Add a short description for your command',
)]
class ThresholdUpsertCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('currency', InputArgument::REQUIRED, 'Threshold currency')
            ->addArgument('level', InputArgument::REQUIRED, 'Threshold level')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $currency = $input->getArgument('currency');
        $level = $input->getArgument('level');

        $threshold = $this->entityManager->getRepository(Threshold::class)->findOneByCurrency($currency);

        if (empty($threshold)) {
            $threshold = new Threshold();
        }

        $threshold
            ->setLevel($level)
            ->setCurrency($currency);

        $this->entityManager->persist($threshold);
        $this->entityManager->flush();

        $io->success('threshold for currency "'.$currency.'" set to "'.$level.'".');

        return Command::SUCCESS;
    }
}
