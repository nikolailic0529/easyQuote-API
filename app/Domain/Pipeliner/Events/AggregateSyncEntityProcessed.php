<?php

namespace App\Domain\Pipeliner\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AggregateSyncEntityProcessed
{
    use Dispatchable;
    use SerializesModels;

    public readonly \DateTimeImmutable $occurrence;

    public function __construct(
        public readonly string $aggregateId,
        public readonly object $entity,
        public readonly string $strategy,
        public readonly ?Model $causer,
    ) {
        $this->occurrence = now()->toDateTimeImmutable();
    }
}
