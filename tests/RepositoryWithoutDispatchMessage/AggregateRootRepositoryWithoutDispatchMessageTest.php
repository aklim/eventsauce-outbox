<?php

declare(strict_types=1);

namespace Tests\RepositoryWithoutDispatchMessage;

use Andreo\EventSauce\Outbox\AggregateRootRepositoryWithoutDispatchMessage;
use EventSauce\EventSourcing\InMemoryMessageRepository;
use EventSauce\EventSourcing\Message;
use PHPUnit\Framework\TestCase;

final class AggregateRootRepositoryWithoutDispatchMessageTest extends TestCase
{
    /**
     * @test
     */
    public function aggregate_versions_are_incremented_per_event(): void
    {
        $messageRepository = new InMemoryMessageRepository();
        $repository = new AggregateRootRepositoryWithoutDispatchMessage(AggregateFake::class, $messageRepository);
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