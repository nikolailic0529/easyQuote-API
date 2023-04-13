<?php

namespace App\Domain\Authorization\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AllowedOpportunityPipelineData extends Data
{
    public function __construct(
        public readonly string $pipelineId,
    ) {
    }
}
