<?php

namespace App\Domain\Company\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class BatchAttachCompanyContactData extends Data
{
    public function __construct(
        #[DataCollectionOf(AttachCompanyContactNoBackrefData::class)]
        public readonly DataCollection $contacts,
    ) {
    }
}
