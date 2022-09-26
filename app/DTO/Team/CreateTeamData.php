<?php

namespace App\DTO\Team;

use App\DTO\SalesUnit\CreateSalesUnitRelationNoBackrefData;
use App\DTO\User\CreateUserRelationNoBackrefData;
use App\Models\BusinessDivision;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CreateTeamData extends Data
{
    public function __construct(
        #[Bail, StringType, Max(100)]
        public readonly string $team_name,
        #[Bail, Uuid, Exists(BusinessDivision::class, 'id')]
        public readonly string $business_division_id,
        #[Bail, Nullable, Numeric, Min(0), Max(999999999)]
        public readonly float|null $monthly_goal_amount,
        #[Bail, Required, ArrayType, DataCollectionOf(CreateUserRelationNoBackrefData::class)]
        public readonly DataCollection $team_leaders,
        #[Bail, Required, ArrayType, DataCollectionOf(CreateSalesUnitRelationNoBackrefData::class)]
        public readonly DataCollection $sales_units,
    ) {
    }
}
