## eventsauce-outbox

Extended message outbox components for EventSauce

```bash
composer require andreo/eventsauce-outbox
```

### Requirements

- PHP ^8.1
- Symfony console ^6.0

### Repository

By default, the EventSauce uses [EventSourcedAggregateRootRepository](https://eventsauce.io/docs/event-sourcing/bootstrap/). 
However, when using the outbox pattern, we do not need to dispatch a message. 
This repository decorates the original repository and ignores dispatch a message

```php

use Andreo\EventSauce\Outbox\EventSourcedAggregateRootRepositoryForOutbox;

new EventSourcedAggregateRootRepositoryForOutbox(
    aggregateRootClassName: $aggregateRootClassName,
    messageRepository: $messageRepository, // EventSauce\EventSourcing\MessageRepository
    regularRepository: $regularRepository // EventSauce\EventSourcing\AggregateRootRepository
)
```

### Forwarding message consumer

This consumer dispatch messages through the message dispatcher 
to the queuing system

```php

use Andreo\EventSauce\Outbox\ForwardingMessageConsumer;

new ForwardingMessageConsumer(
    messageDispatcher: $messageDispatcher // EventSauce\EventSourcing\MessageDispatcher
)
```

### Dispatch outbox messages

```php

use Andreo\EventSauce\Outbox\OutboxProcessMessagesCommand;

new OutboxProcessMessagesCommand(
    relays: $relays, // iterable<EventSauce\MessageOutbox\OutboxRelay>
    logger: $logger, // Psr\Log\LoggerInterface | null
)
```

#### Run dispatching

```bash
php bin/console andreo:event-sauce:outbox-process-messages
```

#### Command options

**--run=true**

- optional
- default: true

**--batch-size=100**

How many messages are to be processed at once

- optional
- default: 100

**--commit-size=1**

How many messages are to be committed at once

- optional
- default: 1

**--sleep=1**

Number of seconds to sleep if the repository is empty

- optional
- default: 1

**--limit=-1**

How many times messages are  to be processed

- optional
- default: -1 (infinity)