<?php

namespace App\Command;

use App\Service\ExchangeRateWatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:exchange-rate-watcher',
    description: 'Add a short description for your command',
)]
class ExchangeRateWatcherCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateWatcher $exchangeRateWatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write('Starting exchange rate monitoring...');
        $this->exchangeRateWatcher->watch();
        $io->success('Execution complete.');

        return Command::SUCCESS;
    }
}
