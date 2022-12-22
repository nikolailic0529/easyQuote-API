<?php

namespace App\DTO\DataAllocation\Stages;

use App\DTO\DataAllocation\SelectAllocationRecordData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ReviewStageData extends Data
{
    public function __construct(
        #[Required, ArrayType]
        #[DataCollectionOf(SelectAllocationRecordData::class)]
        #[MapInputName('selected_opportunities')]
        public readonly DataCollection $selected_records,
    ) {
    }
}