<?php

namespace App\DTO\User;

use App\DTO\Company\CreateCompanyRelationNoBackrefData;
use App\DTO\SalesUnit\CreateSalesUnitRelationNoBackrefData;
use App\Models\Data\Timezone;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule as BaseRule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

final class UpdateUserData extends Data
{
    public function __construct(
        #[Bail, Rule('alpha_spaces')]
        public readonly string|Optional $first_name,
        #[Bail, Rule('alpha_spaces')]
        public readonly string|null|Optional $middle_name,
        #[Bail, Rule('alpha_spaces')]
        public readonly string|Optional $last_name,
        #[Bail, Rule('phone')]
        public readonly string|null|Optional $phone,
        #[Bail, Required, Uuid, Exists(Timezone::class, 'id')]
        public readonly string $timezone_id,
        #[DataCollectionOf(CreateSalesUnitRelationNoBackrefData::class)]
        public readonly DataCollection $sales_units,
        #[DataCollectionOf(CreateCompanyRelationNoBackrefData::class)]
        public readonly DataCollection $companies,
        #[Bail, Required, Uuid]
        public readonly string $role_id,
        #[Bail, Required, Uuid]
        public readonly string $team_id,
        #[Bail, Image, Max(2048)]
        public UploadedFile|Optional $picture,
        public bool|Optional $delete_picture,
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

    public static function attributes(...$args): array
    {
        return [
          'role_id' => 'role',
          'team_id' => 'team',
          'timezone_id' => 'timezone',
        ];
    }
}