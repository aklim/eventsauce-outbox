<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Tests\Command;

use Andreo\EventSauce\Outbox\Command\OutboxMessagesConsumeCommand;
use EventSauce\BackOff\ImmediatelyFailingBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\MessageOutbox\DeleteMessageOnCommit;
use EventSauce\MessageOutbox\InMemoryOutboxRepository;
use EventSauce\MessageOutbox\OutboxRelay;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class OutboxMessagesConsumeCommandTest extends TestCase
{
    /**
     * @test
     */
    public function should_outbox_messages_consume(): void
    {
        $fooOutboxRepository = new InMemoryOutboxRepository();
        $fooOutboxRepository->persist(
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
        );

        $fooMessageConsumerMock = $this->createMock(MessageConsumer::class);
        $fooMessageConsumerMock
            ->expects($this->exactly(7))
            ->method('handle')
        ;

        $fooOutboxRelay = new OutboxRelay(
            $fooOutboxRepository,
            $fooMessageConsumerMock,
            new ImmediatelyFailingBackOffStrategy(),
            new DeleteMessageOnCommit()
        );

        $relays = new ServiceLocator([
            'foo' => fn () => $fooOutboxRelay,
        ]);

        $command = new OutboxMessagesConsumeCommand($relays);
        $tester = new CommandTester($command);

        $tester->execute(['relays' => ['foo'], '--limit' => 2]);
    }

    /**
     * @test
     */
    public function should_outbox_messages_consume_based_on_batch_size(): void
    {
        $fooOutboxRepository = new InMemoryOutboxRepository();
        $fooOutboxRepository->persist(
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
        );

        $fooMessageConsumerMock = $this->createMock(MessageConsumer::class);
        $fooMessageConsumerMock
            ->expects($this->exactly(10))
            ->method('handle')
        ;

        $relay = new OutboxRelay(
            $fooOutboxRepository,
            $fooMessageConsumerMock,
            new ImmediatelyFailingBackOffStrategy(),
            new DeleteMessageOnCommit()
        );

        $relays = new ServiceLocator([
            'foo' => fn () => $relay,
            'bar' => fn () => $relay,
        ]);

        $command = new OutboxMessagesConsumeCommand($relays);
        $tester = new CommandTester($command);

        $tester->execute(['relays' => ['foo', 'bar'], '--limit' => 1, '--batch-size' => 5]);
    }

    /**
     * @test
     */
    public function should_outbox_messages_not_consume(): void
    {
        $fooOutboxRepository = new InMemoryOutboxRepository();
        $fooOutboxRepository->persist(
            new Message(new stdClass()),
            new Message(new stdClass()),
        );

        $fooMessageConsumerMock = $this->createMock(MessageConsumer::class);
        $fooMessageConsumerMock
            ->expects($this->exactly(0))
            ->method('handle')
        ;

        $fooOutboxRelay = new OutboxRelay(
            $fooOutboxRepository,
            $fooMessageConsumerMock,
            new ImmediatelyFailingBackOffStrategy(),
            new DeleteMessageOnCommit()
        );

        $relays = new ServiceLocator([
            'foo' => fn () => $fooOutboxRelay,
        ]);

        $command = new OutboxMessagesConsumeCommand($relays);
        $tester = new CommandTester($command);

        $tester->execute(['relays' => ['foo'], '--run' => 'false', '--limit' => 1]);
    }
}
