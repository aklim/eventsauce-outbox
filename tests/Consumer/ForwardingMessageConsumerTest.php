<?php

declare(strict_types=1);

namespace Tests\Consumer;


use Andreo\EventSauce\Outbox\ForwardingMessageConsumer;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use PHPUnit\Framework\TestCase;
use stdClass;

class ForwardingMessageConsumerTest extends TestCase
{
    /**
     * @test
     */
    public function message_handled(): void
    {
        $dispatcherMock = $this->createMock(MessageDispatcher::class);
        $message = new Message(new stdClass());
        $dispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($message))
        ;

        $consumer = new ForwardingMessageConsumer($dispatcherMock);
        $consumer->handle($message);
    }
}