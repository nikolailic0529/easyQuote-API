<?php

namespace App\DTO\Invitation;

use App\DTO\Company\CreateCompanyRelationNoBackrefData;
use App\DTO\SalesUnit\CreateSalesUnitRelationNoBackrefData;
use App\Enum\CompanyType;
use App\Models\Company;
use App\Models\Role;
use App\Models\SalesUnit;
use App\Models\Team;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class CreateInvitationData extends Data
{
    public function __construct(
        #[Email(Email::FilterEmailValidation)]
        public string $email,
        #[Uuid]
        public string $role_id,
        #[Uuid]
        public ?string $team_id,
        #[Required, StringType, Url]
        public ?string $host,
        #[DataCollectionOf(CreateSalesUnitRelationNoBackrefData::class)]
        public DataCollection $sales_units,
        #[DataCollectionOf(CreateCompanyRelationNoBackrefData::class)]
        public DataCollection $companies
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'email' => [
                Rule::unique('users', 'email')
                    ->withoutTrashed(),
                Rule::unique('invitations', 'email')
                    ->withoutTrashed(),
            ],
            'role_id' => [
                Rule::exists(Role::class, (new Role())->getKeyName())
                    ->withoutTrashed(),
            ],
            'team_id' => [
                Rule::exists(Team::class, (new Team())->getKeyName())
                    ->withoutTrashed(),
            ],
            'sales_units.*.id' => [
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())
                    ->withoutTrashed(),
            ],
            'companies.*.id' => [
                Rule::exists(Company::class, (new Company())->getKeyName())
                    ->where('type', CompanyType::INTERNAL)
                    ->withoutTrashed(),
            ],
        ];
    }
}