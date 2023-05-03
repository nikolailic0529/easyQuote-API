<?php

namespace App\Domain\Authorization\DataTransferObjects;

use App\Domain\Authorization\Enum\AccessEntityDirection;
use App\Domain\Authorization\Enum\AccessEntityPipelineDirection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AccessData extends Data
{
    public function __construct(
        public readonly AccessEntityDirection $accessContactDirection = AccessEntityDirection::Owned,
        public readonly AccessEntityDirection $accessCompanyDirection = AccessEntityDirection::Owned,
        public readonly AccessEntityDirection $accessOpportunityDirection = AccessEntityDirection::Owned,
        public readonly AccessEntityPipelineDirection $accessOpportunityPipelineDirection = AccessEntityPipelineDirection::All,
        #[DataCollectionOf(AllowedOpportunityPipelineData::class)]
        public readonly DataCollection $allowedOpportunityPipelines = new DataCollection(AllowedOpportunityPipelineData::class, []),
        public readonly AccessEntityDirection $accessWorldwideQuoteDirection = AccessEntityDirection::Owned,
        public readonly AccessEntityDirection $accessSalesOrderDirection = AccessEntityDirection::Owned,
    ) {
    }
}
