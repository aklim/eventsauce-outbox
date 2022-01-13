<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox;

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
use EventSauce\EventSourcing\UnableToReconstituteAggregateRoot;
use Generator;
use Throwable;
use function count;

/**
 * @template T of AggregateRoot
 *
 * @template-implements AggregateRootRepository<T>
 */
final class AggregateRootRepositoryWithoutDispatchMessage implements AggregateRootRepository
{
    /**
     * @param class-string<T> $aggregateRootClassName
     */
    public function __construct(
        private string $aggregateRootClassName,
        private MessageRepository $messageRepository,
        private MessageDecorator $decorator = new DefaultHeadersDecorator(),
        private ClassNameInflector $classNameInflector = new DotSeparatedSnakeCaseInflector()
    ) {
    }

    /**
     * @return T
     */
    public function retrieve(AggregateRootId $aggregateRootId): object
    {
        try {
            /** @var class-string<T> $className */
            $className = $this->aggregateRootClassName;
            $events = $this->retrieveAllEvents($aggregateRootId);

            return $className::reconstituteFromEvents($aggregateRootId, $events);
        } catch (Throwable $throwable) {
            throw UnableToReconstituteAggregateRoot::becauseOf($throwable->getMessage(), $throwable);
        }
    }

    /**
     * @return Generator<object>
     */
    private function retrieveAllEvents(AggregateRootId $aggregateRootId): Generator
    {
        /** @var Generator<Message> $messages */
        $messages = $this->messageRepository->retrieveAll($aggregateRootId);

        foreach ($messages as $message) {
            yield $message->event();
        }

        return $messages->getReturn();
    }

    /**
     * @param T $aggregateRoot
     */
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
