<?php

declare(strict_types=1);

namespace Tests\ProcessOutboxMessages;

use Andreo\EventSauce\Outbox\OutboxProcessMessagesCommand;
use EventSauce\BackOff\ImmediatelyFailingBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\MessageOutbox\DeleteMessageOnCommit;
use EventSauce\MessageOutbox\InMemoryOutboxRepository;
use EventSauce\MessageOutbox\OutboxRelay;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Tester\CommandTester;

final class OutboxProcessMessagesCommandTest extends TestCase
{
    /**
     * @test
     */
    public function should_messages_published(): void
    {
        $fooOutboxRepository = new InMemoryOutboxRepository();
        $fooOutboxRepository->persist(
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
        );

        $barOutboxRepository = new InMemoryOutboxRepository();
        $barOutboxRepository->persist(
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
        );

        $fooMessageConsumerMock = $this->createMock(MessageConsumer::class);
        $fooMessageConsumerMock
            ->expects($this->exactly(3))
            ->method('handle')
        ;

        $barMessageConsumerMock = $this->createMock(MessageConsumer::class);
        $barMessageConsumerMock
            ->expects($this->exactly(5))
            ->method('handle')
        ;

        $fooOutboxRelay = new OutboxRelay(
            $fooOutboxRepository,
            $fooMessageConsumerMock,
            new ImmediatelyFailingBackOffStrategy(),
            new DeleteMessageOnCommit()
        );
        $barOutboxRelay = new OutboxRelay(
            $barOutboxRepository,
            $barMessageConsumerMock,
            new ImmediatelyFailingBackOffStrategy(),
            new DeleteMessageOnCommit()
        );

        $command = new OutboxProcessMessagesCommand([$fooOutboxRelay, $barOutboxRelay]);
        $tester = new CommandTester($command);

        $tester->execute(['--limit' => 2]);
    }

    /**
     * @test
     */
    public function should_messages_published_based_on_batch_size(): void
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
            ->expects($this->exactly(5))
            ->method('handle')
        ;

        $fooOutboxRelay = new OutboxRelay(
            $fooOutboxRepository,
            $fooMessageConsumerMock,
            new ImmediatelyFailingBackOffStrategy(),
            new DeleteMessageOnCommit()
        );

        $command = new OutboxProcessMessagesCommand([$fooOutboxRelay]);
        $tester = new CommandTester($command);

        $tester->execute(['--limit' => 1, '--batch-size' => 5]);
    }

    /**
     * @test
     */
    public function should_messages_not_published__if_watching_is_disabled(): void
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

        $command = new OutboxProcessMessagesCommand([$fooOutboxRelay]);
        $tester = new CommandTester($command);

        $tester->execute(['--run' => '0', '--limit' => 1]);
    }
}
