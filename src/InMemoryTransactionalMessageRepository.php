<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\MessageOutbox\OutboxRepository;
use Generator;
use Throwable;

final class InMemoryTransactionalMessageRepository implements MessageRepository
{
    public function __construct(
        private MessageRepository $messageRepository,
        private OutboxRepository $outboxRepository
    ) {
    }

    public function persist(Message ...$messages): void
    {
        try {
            $this->messageRepository->persist(...$messages);
            $this->outboxRepository->persist(...$messages);
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        return $this->messageRepository->retrieveAll($id);
    }

    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        return $this->messageRepository->retrieveAllAfterVersion($id, $aggregateRootVersion);
    }
}
