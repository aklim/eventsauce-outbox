## eventsauce-outbox 3.0

Extended message outbox components for EventSauce

```bash
composer require andreo/eventsauce-outbox
```

### Requirements

- PHP >=8.2
- Symfony console ^6.2

#### Previous version docs

- [2.0](https://github.com/eventsauce-symfony/eventsauce-outbox/tree/2.0.1)

### Repository without message dispatching

```php

use Andreo\EventSauce\Outbox\Repository\EventSourcedAggregateRootRepositoryForOutbox;

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

use Andreo\EventSauce\Outbox\MessageConsumer\ForwardingMessageConsumer;

new ForwardingMessageConsumer(
    messageDispatcher: $messageDispatcher // EventSauce\EventSourcing\MessageDispatcher
)
```

### Command to dispatching messages from the outbox

```php

use Andreo\EventSauce\Outbox\Command\OutboxMessagesConsumeCommand;

new OutboxMessagesConsumeCommand(
    relays: $relays, // Symfony\Component\DependencyInjection\ServiceLocator<EventSauce\MessageOutbox\OutboxRelay>
    logger: $logger, // ?Psr\Log\LoggerInterface
)
```

```bash
php bin/console andreo:eventsauce:message-outbox:consume foo-relay-id
```

#### Command options

**relays**

- required
- string[]

`Relay ids registered in service locator`

**--run=true**

- optional
- default: true

`Processing messages run`

**--batch-size=100**

`How many messages are to be retrieve batch`

- optional
- default: 100

**--commit-size=1**

`How many messages are to be committed at once`

- optional
- default: 1

**--sleep=1**

`Number of seconds to sleep if the repository is empty`

- optional
- default: 1

**--limit=-1**

`How many times messages are to be processed`

- optional
- default: -1 (infinity)