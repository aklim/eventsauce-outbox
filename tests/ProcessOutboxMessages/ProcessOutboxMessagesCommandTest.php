<?php

declare(strict_types=1);

namespace Tests\ProcessOutboxMessages;

use Andreo\EventSauce\Outbox\ProcessOutboxMessagesCommand;
use EventSauce\BackOff\ImmediatelyFailingBackOffStrategy;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\MessageOutbox\DeleteMessageOnCommit;
use EventSauce\MessageOutbox\InMemoryOutboxRepository;
use EventSauce\MessageOutbox\OutboxRelay;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Tester\CommandTester;

final class ProcessOutboxMessagesCommandTest extends TestCase
{
    /**
     * @test
     */
    public function messages_publishing(): void
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

        $command = new ProcessOutboxMessagesCommand([$fooOutboxRelay, $barOutboxRelay]);
        $tester = new CommandTester($command);

        $tester->execute(['--limit' => 2]);
    }
}
