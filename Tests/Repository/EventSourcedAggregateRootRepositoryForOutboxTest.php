<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Tests\Repository;

use Andreo\EventSauce\Outbox\Repository\EventSourcedAggregateRootRepositoryForOutbox;
use EventSauce\EventSourcing\EventSourcedAggregateRootRepository;
use EventSauce\EventSourcing\InMemoryMessageRepository;
use EventSauce\EventSourcing\Message;
use PHPUnit\Framework\TestCase;

final class EventSourcedAggregateRootRepositoryForOutboxTest extends TestCase
{
    /**
     * @test
     */
    public function should_repository_behaviour_is_valid(): void
    {
        $messageRepository = new InMemoryMessageRepository();
        $repository = new EventSourcedAggregateRootRepositoryForOutbox(
            AggregateFake::class,
            $messageRepository,
            new EventSourcedAggregateRootRepository(
                AggregateFake::class,
                $messageRepository
            ),
        );
        $aggregateRootId = DummyAggregateId::create();
        $aggregate = $repository->retrieve($aggregateRootId);
        $this->assertInstanceOf(AggregateFake::class, $aggregate);
        $aggregate->increment();
        $aggregate->increment();
        $aggregate->increment();
        $repository->persist($aggregate);

        /** @var Message[] $messages */
        $messages = iterator_to_array($messageRepository->retrieveAll($aggregateRootId));
        self::assertEquals(1, $messages[0]->aggregateVersion());
        self::assertEquals(2, $messages[1]->aggregateVersion());
        self::assertEquals(3, $messages[2]->aggregateVersion());
    }
}
