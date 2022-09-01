<?php

namespace App\DTO\User;

use App\DTO\Company\CreateCompanyRelationNoBackrefData;
use App\DTO\SalesUnit\CreateSalesUnitRelationNoBackrefData;
use App\Models\Data\Timezone;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Support\Optional;
use Illuminate\Validation\Rule as BaseRule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Symfony\Component\Validator\Constraints\Uuid;

final class UpdateUserData extends Data
{
    public function __construct(
        #[Rule('alpha_spaces')]
        public readonly string|Optional $first_name,
        #[Rule('alpha_spaces')]
        public readonly string|null|Optional $middle_name,
        #[Rule('alpha_spaces')]
        public readonly string|Optional $last_name,
        #[Rule('phone')]
        public readonly string|null|Optional $phone,
        #[Uuid, Exists(Timezone::class, 'id')]
        public readonly string $timezone_id,
        #[DataCollectionOf(CreateSalesUnitRelationNoBackrefData::class)]
        public readonly DataCollection $sales_units,
        #[DataCollectionOf(CreateCompanyRelationNoBackrefData::class)]
        public readonly DataCollection $companies,
        #[Uuid]
        public readonly string $role_id,
        #[Uuid]
        public readonly string $team_id
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'role_id' => [
                BaseRule::exists(Role::class, 'id')->whereNotNull('activated_at')->withoutTrashed(),
            ],
            'team_id' => [
                BaseRule::exists(Team::class, 'id')->withoutTrashed(),
            ],
        ];
    }


}