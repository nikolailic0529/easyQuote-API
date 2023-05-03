<?php

namespace App\Domain\Company\DataTransferObjects;

use App\Domain\Company\Models\Company;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class CreateCompanyRelationNoBackrefData extends Data
{
    public function __construct(
        #[Uuid, Exists(Company::class, 'id')]
        public readonly string $id
    ) {
    }
}
