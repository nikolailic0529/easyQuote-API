<?php

namespace App\Domain\SalesUnit\DataTransferObjects;

use App\Domain\SalesUnit\Models\SalesUnit;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class CreateSalesUnitRelationNoBackrefData extends Data
{
    public function __construct(
        #[Uuid, Exists(SalesUnit::class, 'id')]
        public readonly string $id
    ) {
    }
}
