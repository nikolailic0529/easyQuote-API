<?php

namespace App\DTO\DataAllocation;

use App\Models\DataAllocation\DataAllocationRecord;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class SelectAllocationRecordData extends Data
{
    public function __construct(
        #[Uuid, Exists(DataAllocationRecord::class)]
        public readonly string $id
    ) {
    }
}