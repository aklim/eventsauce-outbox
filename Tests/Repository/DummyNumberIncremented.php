<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Tests\Repository;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class DummyNumberIncremented implements SerializablePayload
{
    private int $number;

    public function __construct(int $number)
    {
        $this->number = $number;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function toPayload(): array
    {
        return [
            'number' => $this->number,
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new self($payload['number']);
    }
}
