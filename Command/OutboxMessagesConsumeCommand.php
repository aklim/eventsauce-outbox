<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Command;

use EventSauce\MessageOutbox\OutboxRelay;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Throwable;

#[AsCommand(
    name: 'andreo:eventsauce:message-outbox:consume',
)]
final class OutboxMessagesConsumeCommand extends Command
{
    /**
     * @param ServiceLocator<OutboxRelay> $relays
     */
    public function __construct(
        private readonly ServiceLocator $relays,
        private ?LoggerInterface $logger = null
    ) {
        parent::__construct();

        $this->logger = $logger ?? new NullLogger();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'relays',
                mode: InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                description: 'Relays to be run',
            )
            ->addOption(
                name: 'run',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'Processing messages run',
                default: true
            )
            ->addOption(
                name: 'batch-size',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'How many messages are to be retrieve batch',
                default: 100
            )
            ->addOption(
                name: 'commit-size',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'How many messages are to be committed at once',
                default: 1
            )
            ->addOption(
                name: 'sleep',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'Number of seconds to sleep if the repository is empty.',
                default: 1
            )
            ->addOption(
                name: 'limit',
                mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_REQUIRED,
                description: 'How many times messages are to be processed',
                default: -1
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Dispatching messages from the outbox has been run...');

        $run = filter_var($input->getOption('run'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        /** @var string[] $relayIds */
        $relayIds = $input->getArgument('relays');
        $batchSize = $input->getOption('batch-size');
        $commitSize = $input->getOption('commit-size');
        $sleep = $input->getOption('sleep');
        $limit = $input->getOption('limit');

        if (!is_bool($run) ||
            !is_numeric($batchSize) ||
            !is_numeric($commitSize) ||
            !is_numeric($sleep) ||
            !is_numeric($limit)
        ) {
            $output->writeln('Invalid arguments.');

            return self::INVALID;
        }

        $batchSize = (int) $batchSize;
        $commitSize = (int) $commitSize;
        $sleep = (int) $sleep;
        $limit = (int) $limit;

        $processCounter = 0;
        while ($run && (-1 === $limit || $processCounter < $limit)) {
            foreach ($relayIds as $relayId) {
                try {
                    $relay = $this->relays->get($relayId);
                    assert($relay instanceof OutboxRelay);
                } catch (ContainerExceptionInterface $e) {
                    $output->writeln(sprintf('Relay %s not found', $relayId));

                    return self::INVALID;
                }

                try {
                    $numberOfDispatchedMessages = $relay->publishBatch($batchSize, $commitSize);
                    if ($numberOfDispatchedMessages > 0) {
                        $this->logger->info(
                            'Relay: {relay} has dispatched {number} messages.',
                            [
                                'relay' => $relayId,
                                'number' => $numberOfDispatchedMessages,
                            ]
                        );
                    }
                } catch (Throwable $throwable) {
                    $this->logger->critical(
                        'Failed to dispatch messages from the outbox. Error: {error}, Relay: {relay}.',
                        [
                            'error' => $throwable->getMessage(),
                            'relay' => $relayId,
                            'exception' => $throwable,
                        ]
                    );
                    $output->writeln('Failed to dispatch messages from the outbox.');

                    return self::FAILURE;
                }

                if (0 === $numberOfDispatchedMessages) {
                    sleep($sleep);
                }
            }

            ++$processCounter;
        }

        $output->writeln('Dispatching a messages from the outbox was successful.');

        return self::SUCCESS;
    }
}
