<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Repository;

use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\ClassNameInflector;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDecorator;
use EventSauce\EventSourcing\MessageRepository;
use function count;

/**
 * @template T of AggregateRoot
 *
 * @implements AggregateRootRepository<T>
 */
final readonly class EventSourcedAggregateRootRepositoryForOutbox implements AggregateRootRepository
{
    /**
     * @param class-string<T>            $aggregateRootClassName
     * @param AggregateRootRepository<T> $regularRepository
     */
    public function __construct(
        private string $aggregateRootClassName,
        private MessageRepository $messageRepository,
        private AggregateRootRepository $regularRepository,
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private ClassNameInflector $classNameInflector = new DotSeparatedSnakeCaseInflector()
    ) {
    }

    /**
     * @return T
     */
    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        return $this->regularRepository->retrieve($aggregateRootId);
    }

    public function persist(object $aggregateRoot): void
    {
        assert($aggregateRoot instanceof AggregateRoot, 'Expected $aggregateRoot to be an instance of ' . AggregateRoot::class);

        $this->persistEvents(
            $aggregateRoot->aggregateRootId(),
            $aggregateRoot->aggregateRootVersion(),
            ...$aggregateRoot->releaseEvents()
        );
    }

    public function persistEvents(AggregateRootId $aggregateRootId, int $aggregateRootVersion, object ...$events): void
    {
        if (0 === count($events)) {
            return;
        }

        $aggregateRootVersion -= count($events);
        $metadata = [
            Header::AGGREGATE_ROOT_ID => $aggregateRootId,
            Header::AGGREGATE_ROOT_TYPE => $this->classNameInflector->classNameToType($this->aggregateRootClassName),
        ];
        $messages = array_map(function (object $event) use ($metadata, &$aggregateRootVersion) {
            return $this->decorator->decorate(new Message(
                $event,
                $metadata + [Header::AGGREGATE_ROOT_VERSION => ++$aggregateRootVersion]
            ));
        }, $events);

        $this->messageRepository->persist(...$messages);
    }
}
