<?php

namespace App\Events\Pipeliner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AggregateSyncEntityProcessed
{
    use Dispatchable, SerializesModels;

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
