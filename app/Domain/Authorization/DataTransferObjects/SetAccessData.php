<?php

namespace App\Domain\Authorization\DataTransferObjects;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Authorization\Enum\AccessEntityPipelineDirection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;

#[MapName(SnakeCaseMapper::class)]
final class SetAccessData extends Data
{
    public function __construct(
        public readonly AccessEntityDirection|Optional $accessContactDirection,
        public readonly AccessEntityDirection|Optional $accessCompanyDirection,
        public readonly AccessEntityDirection|Optional $accessOpportunityDirection,
        public readonly AccessEntityPipelineDirection|Optional $accessOpportunityPipelineDirection,
        #[DataCollectionOf(AllowedOpportunityPipelineData::class)]
        public readonly DataCollection|Optional $allowedOpportunityPipelines,
        public readonly AccessEntityDirection|Optional $accessWorldwideQuoteDirection,
        public readonly AccessEntityDirection|Optional $accessSalesOrderDirection,
    ) {
    }
}
