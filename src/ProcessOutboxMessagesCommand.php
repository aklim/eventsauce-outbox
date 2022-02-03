<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox;

use EventSauce\MessageOutbox\OutboxRelay;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ProcessOutboxMessagesCommand extends Command
{
    /**
     * @param iterable<string, OutboxRelay> $relays
     */
    public function __construct(
        private iterable $relays,
        private LoggerInterface $logger = new NullLogger(),
        private bool $shouldRun = true,
        private int $batchSize = 100,
        private int $commitSize = 1,
        private int $rest = 1,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'limit',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'How many times are messages to be processed',
            default: -1
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running...');

        /** @var int $limit */
        $limit = $input->getOption('limit');

        $processCounter = 0;
        while ($this->shouldRun && (-1 === $limit || $processCounter < $limit)) {
            $numberOfMessagesDispatched = 0;
            try {
                foreach ($this->relays as $name => $relay) {
                    $numberOfMessagesDispatched += $relay->publishBatch($this->batchSize, $this->commitSize, );
                }
            } catch (Throwable $throwable) {
                $this->logger->critical(
                    'Process outbox messages failed. Error: {error}, Relay {relay}',
                    [
                        'error' => $throwable->getMessage(),
                        'relay' => $name,
                        'exception' => $throwable,
                    ]
                );
                $output->writeln('Closed.');

                return 1;
            }

            if (0 === $numberOfMessagesDispatched) {
                sleep($this->rest);
            }

            ++$processCounter;
        }

        $output->writeln('Done.');

        return 0;
    }
}
