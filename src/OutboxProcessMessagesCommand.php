<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox;

use EventSauce\MessageOutbox\OutboxRelay;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'andreo:event-sauce:outbox-process-messages',
    description: 'Outbox messages dispatching'
)]
final class OutboxProcessMessagesCommand extends Command
{
    /**
     * @param iterable<string, OutboxRelay> $relays
     */
    public function __construct(
        private iterable $relays,
        private LoggerInterface $logger = new NullLogger()
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'run',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'Processing messages run',
                default: true
            )
            ->addOption(
                name: 'batch-size',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'How many messages are to be processed at once',
                default: 100
            )
            ->addOption(
                name: 'commit-size',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'How many messages are to be committed at once',
                default: 1
            )
            ->addOption(
                name: 'rest',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'Number of seconds to rest if the repository is empty.',
                default: 1
            )
            ->addOption(
                name: 'limit',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'How many times are messages to be processed',
                default: -1
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running...');

        $run = filter_var($input->getOption('run'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $batchSize = $input->getOption('batch-size');
        $commitSize = $input->getOption('commit-size');
        $rest = $input->getOption('rest');
        $limit = $input->getOption('limit');

        if (!is_bool($run) || !is_numeric($batchSize) || !is_numeric($commitSize) || !is_numeric($rest) || !is_numeric($limit)) {
            $output->writeln('Invalid input. Check your parameters.');

            return self::INVALID;
        }

        $batchSize = (int) $batchSize;
        $commitSize = (int) $commitSize;
        $rest = (int) $rest;
        $limit = (int) $limit;

        $processCounter = 0;
        while ($run && (-1 === $limit || $processCounter < $limit)) {
            $numberOfMessagesDispatched = 0;
            try {
                foreach ($this->relays as $name => $relay) {
                    if (0 < $number = $relay->publishBatch($batchSize, $commitSize)) {
                        $this->logger->info('Relay {relay} dispatched {number} messages.', ['relay' => $name, 'number', $number]);
                    }
                    $numberOfMessagesDispatched += $number;
                }
            } catch (Throwable $throwable) {
                $this->logger->critical(
                    'Process outbox messages failed. Error: {error}, Relay {relay}.',
                    [
                        'error' => $throwable->getMessage(),
                        'relay' => $name,
                        'exception' => $throwable,
                    ]
                );
                $output->writeln('Closed.');

                return self::FAILURE;
            }

            if (0 === $numberOfMessagesDispatched) {
                sleep($rest);
            }

            ++$processCounter;
        }

        $output->writeln('Done.');

        return self::SUCCESS;
    }
}
