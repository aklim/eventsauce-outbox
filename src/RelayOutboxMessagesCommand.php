<?php

declare(strict_types=1);


namespace Andreo\EventSauce\Outbox;

use EventSauce\MessageOutbox\OutboxRelay;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RelayOutboxMessagesCommand extends Command
{
    public function __construct(
        private OutboxRelay $relay,
        private bool $shouldRun = true,
        private int $batchSize = 100,
        private int $commitSize = 1
    ){
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'once',
            null,
            InputOption::VALUE_OPTIONAL,
            'Run once',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running...');
        $once = $input->getOption('once');
        if ($once) {
            $this->publishBatch();
            return 0;
        }

        while($this->shouldRun) {
            $numberOfMessagesDispatched = $this->publishBatch();

            if (0 === $numberOfMessagesDispatched) {
                sleep(1);
            }
        }

        return 0;
    }

    private function publishBatch(): int
    {
        return $this->relay->publishBatch(
            $this->batchSize,
            $this->commitSize,
        );
    }
}