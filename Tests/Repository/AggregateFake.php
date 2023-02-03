<?php

declare(strict_types=1);

namespace Andreo\EventSauce\Outbox\Tests\Repository;

use EventSauce\EventSourcing\AggregateRootBehaviour;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Snapshotting\AggregateRootWithSnapshotting;
use EventSauce\EventSourcing\Snapshotting\SnapshottingBehaviour;

class AggregateFake implements AggregateRootWithSnapshotting
{
    use AggregateRootBehaviour;
    use SnapshottingBehaviour;

    private int $incrementedNumber = 0;

    public function increment(): void
    {
        $this->recordThat(
            new DummyNumberIncremented(
                $this->incrementedNumber + 1
            )
        );
    }

    protected function applyDummyNumberIncremented(DummyNumberIncremented $event): void
    {
        $this->incrementedNumber = $event->getNumber();
    }

    protected function createSnapshotState(): int
    {
        return $this->incrementedNumber;
    }

    public function getIncrementedNumber(): int
    {
        return $this->incrementedNumber;
    }

    protected static function reconstituteFromSnapshotState(AggregateRootId $id, $state): AggregateRootWithSnapshotting
    {
        $new = new self($id);
        $new->incrementedNumber = $state;

        return $new;
    }
}
