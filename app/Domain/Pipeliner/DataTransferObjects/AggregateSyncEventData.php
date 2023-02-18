<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

final class AggregateSyncEventData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly \DateTimeInterface $occurred_at,
        #[MapInputName('payload.aggregate_id')]
        public readonly string $aggregate_id,
        #[MapInputName('payload.success')]
        public readonly bool $success,
        #[MapInputName('payload.processed_counts')]
        public readonly AggregateSyncCountsData $processed_counts = new AggregateSyncCountsData(),
        #[MapInputName('payload.skipped_counts')]
        public readonly AggregateSyncCountsData $skipped_counts = new AggregateSyncCountsData()
    ) {
    }
}
