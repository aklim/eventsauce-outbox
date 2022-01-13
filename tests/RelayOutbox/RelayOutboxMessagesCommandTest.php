<?php

declare(strict_types=1);

namespace Tests\RelayOutbox;

use Andreo\EventSauce\Outbox\RelayOutboxMessagesCommand;
use ArrayIterator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\MessageOutbox\OutboxRelay;
use EventSauce\MessageOutbox\OutboxRepository;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Console\Tester\CommandTester;

final class RelayOutboxMessagesCommandTest extends TestCase
{
    /**
     * @test
     */
    public function messages_publishing(): void
    {
        $outboxRepositoryMock = $this->createConfiguredMock(OutboxRepository::class, [
            'retrieveBatch' => $this->messages(),
        ]);

        $messageConsumerMock = $this->createMock(MessageConsumer::class);
        $messageConsumerMock
            ->expects($this->exactly(3))
            ->method('handle')
        ;

        $outboxRelay = new OutboxRelay($outboxRepositoryMock, $messageConsumerMock);
        $command = new RelayOutboxMessagesCommand($outboxRelay);
        $tester = new CommandTester($command);
        $tester->execute(['--once' => true]);
    }

    private function messages(): ArrayIterator
    {
        return new ArrayIterator([
            new Message(new stdClass()),
            new Message(new stdClass()),
            new Message(new stdClass()),
        ]);
    }
}
