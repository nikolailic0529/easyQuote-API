<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class BatchRestoreSyncErrorData extends Data
{
    public function __construct(
        #[DataCollectionOf(RestoreSyncErrorData::class)]
        public readonly DataCollection $syncErrors,
    ) {
    }
}
