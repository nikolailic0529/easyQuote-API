<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class BatchArchiveSyncErrorData extends Data
{
    public function __construct(
        #[DataCollectionOf(ArchiveSyncErrorData::class)]
        public readonly DataCollection $syncErrors,
    ) {
    }
}
