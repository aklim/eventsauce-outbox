<?php

declare(strict_types=1);


namespace Andreo\EventSauce\Outbox;

use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\MessageDispatcher;

final class ForwardingMessageConsumer implements MessageConsumer
{
    private MessageDispatcher $messageDispatcher;

    public function __construct(MessageDispatcher $messageDispatcher)
    {
        $this->messageDispatcher = $messageDispatcher;
    }

    public function handle(Message $message): void
    {
        $this->messageDispatcher->dispatch($message);
    }
}