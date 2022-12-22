<?php

namespace App\DTO\DataAllocation\Stages;

use App\DTO\DataAllocation\AssignToUserData;
use App\Enum\DistributionAlgorithmEnum;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\DataAllocation\DataAllocationFile;
use DateTime;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\AfterOrEqual;
use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

class ImportStageData extends Data
{
    public function __construct(
        #[Bail, Uuid]
        public string $company_id,
        #[Bail, Uuid]
        public string $business_division_id,
        #[Bail, Uuid]
        public string $file_id,
        #[DateFormat('Y-m-d')]
        #[WithCast(DateTimeInterfaceCast::class, type: DateTime::class, format: 'Y-m-d')]
        public \DateTimeInterface $assignment_start_date,
        #[Nullable, DateFormat('Y-m-d'), AfterOrEqual('assignment_start_date')]
        #[WithCast(DateTimeInterfaceCast::class, type: DateTime::class, format: 'Y-m-d')]
        public \DateTimeInterface|Optional|null $assignment_end_date,
        #[DataCollectionOf(AssignToUserData::class)]
        public DataCollection $assigned_users,
        public DistributionAlgorithmEnum|Optional $distribution_algorithm = DistributionAlgorithmEnum::Evenly
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'company_id' => [
                Rule::exists(Company::class, (new Company())->getKeyName())
                    ->where('type', 'Internal')
                    ->withoutTrashed(),
            ],
            'business_division_id' => [
                Rule::exists(BusinessDivision::class, (new BusinessDivision())->getKeyName()),
            ],
            'file_id' => [
                Rule::exists(DataAllocationFile::class, (new DataAllocationFile())->getKeyName())
                    ->withoutTrashed(),
            ],
        ];
    }
}