<?php

namespace App\DTO\DataAllocation;

use App\DTO\Access\PermissionsData;
use App\DTO\DataPipes\PermissionsDataPipe;
use App\DTO\Transformers\EnumNameTransformer;
use App\Enum\DataAllocationStageEnum;
use App\Enum\DistributionAlgorithmEnum;
use DateTimeInterface;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataPipeline;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

final class DataAllocationInListData extends Data
{
    public function __construct(
        public string $id,
        public DistributionAlgorithmEnum $distribution_algorithm,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public DateTimeInterface|null $assignment_start_date,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public DateTimeInterface|null $assignment_end_date,
        #[WithTransformer(EnumNameTransformer::class)]
        public DataAllocationStageEnum $stage,
        public ?string $company_name,
        public ?string $division_name,
        public PermissionsData $permissions,
        public DateTimeInterface|null $created_at,
        public DateTimeInterface|null $updated_at,
    ) {
    }

    public static function pipeline(): DataPipeline
    {
        return parent::pipeline()
            ->through(PermissionsDataPipe::class);
    }

    public function with(): array
    {
        return [
            'stage_value' => $this->stage->value,
        ];
    }
}