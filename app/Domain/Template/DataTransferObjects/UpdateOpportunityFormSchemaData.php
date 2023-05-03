<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\LaravelData\Data;

final class UpdateOpportunityFormSchemaData extends Data
{
    public function __construct(
        public readonly array $form_data,
        public readonly array $sidebar_0,
    ) {
    }
}
