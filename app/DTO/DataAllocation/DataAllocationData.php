<?php

namespace App\DTO\DataAllocation;

use App\DTO\Transformers\EnumNameTransformer;
use App\DTO\User\UserAsRelationData;
use App\Enum\DataAllocationRecordResultEnum;
use App\Enum\DataAllocationStageEnum;
use App\Enum\DistributionAlgorithmEnum;
use App\Models\DataAllocation\DataAllocation;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class DataAllocationData extends Data
{
    public function __construct(
        public string $id,
        public string|null $company_id,
        public string|null $business_division_id,
        public string|null $file_id,
        #[WithTransformer(EnumNameTransformer::class)]
        public DataAllocationStageEnum $stage,
        public DistributionAlgorithmEnum|null $distribution_algorithm,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public \DateTimeInterface|null $assignment_start_date,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public \DateTimeInterface|null $assignment_end_date,
        public Lazy|DataAllocationFileData $file,
        #[DataCollectionOf(DataAllocationRecordData::class)]
        public Lazy|DataCollection $opportunities,
        #[DataCollectionOf(DataAllocationRecordData::class)]
        public Lazy|DataCollection $processed_opportunities,
        #[DataCollectionOf(UserAsRelationData::class)]
        public Lazy|DataCollection $assigned_users,
        #[WithCast(DateTimeInterfaceCast::class, type: \DateTime::class)]
        public \DateTimeInterface|null $created_at,
        #[WithCast(DateTimeInterfaceCast::class, type: \DateTime::class)]
        public \DateTimeInterface|null $updated_at
    ) {
    }

    public static function allowedRequestIncludes(): ?array
    {
        return ['assigned_users', 'file', 'opportunities', 'processed_opportunities'];
    }

    public static function fromModel(DataAllocation $allocation): static
    {
        return new static(
            id: $allocation->getKey(),
            company_id: $allocation->company()->getParentKey(),
            business_division_id: $allocation->businessDivision()->getParentKey(),
            file_id: $allocation->file()->getParentKey(),
            stage: $allocation->stage,
            distribution_algorithm: $allocation->distribution_algorithm,
            assignment_start_date: $allocation->assignment_start_date,
            assignment_end_date: $allocation->assignment_end_date,
            file: Lazy::create(static fn() => DataAllocationFileData::optional($allocation->file)),
            opportunities: Lazy::create(static function () use ($allocation): DataCollection {
                $collection = $allocation->file?->allocationRecords ?? new Collection();

                $collection
                    ->loadMissing([
                        'assignedUser:id,email,first_name,middle_name,last_name,user_fullname',
                        'opportunity.contractType:id,type_short_name',
                        'opportunity.importedPrimaryAccount:id,company_name',
                        'opportunity.pipelineStage:id,stage_order,stage_name',
                        'opportunity.accountManager:id,user_fullname',
                    ]);

                return DataAllocationRecordData::collection($collection);
            }),
            processed_opportunities: Lazy::create(static function () use ($allocation): DataCollection {
                $collection = $allocation->file?->allocationRecords ?? new Collection();

                $collection
                    ->loadMissing([
                        'assignedUser:id,email,first_name,middle_name,last_name,user_fullname',
                        'opportunity.contractType:id,type_short_name',
                        'opportunity.importedPrimaryAccount:id,company_name',
                        'opportunity.pipelineStage:id,stage_order,stage_name',
                        'opportunity.accountManager:id,user_fullname',
                    ]);

                $collection = $collection
                    ->lazy()
                    ->where('result', '!==', DataAllocationRecordResultEnum::Unprocessed)
                    ->where('is_selected', true)
                    ->values()
                    ->all();

                return DataAllocationRecordData::collection($collection);
            }),
            assigned_users: Lazy::create(static fn() => UserAsRelationData::collection($allocation->assignedUsers)),
            created_at: $allocation->{$allocation->getCreatedAtColumn()},
            updated_at: $allocation->{$allocation->getUpdatedAtColumn()},
        );
    }

    public function with(): array
    {
        return [
            'stage_value' => $this->stage->value,
        ];
    }
}