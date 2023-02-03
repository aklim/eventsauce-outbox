<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Tests\MessageConsumer;

use Andreo\EventSauce\Outbox\MessageConsumer\ForwardingMessageConsumer;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use PHPUnit\Framework\TestCase;
use stdClass;

class ForwardingMessageConsumerTest extends TestCase
{
    /**
     * @test
     */
    public function should_forward_message_by_dispatcher(): void
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
