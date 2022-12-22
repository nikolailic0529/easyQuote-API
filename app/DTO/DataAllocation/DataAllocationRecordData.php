<?php

namespace App\DTO\DataAllocation;

use App\DTO\User\UserAsRelationData;
use App\Enum\DataAllocationRecordResultEnum;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class DataAllocationRecordData extends Data
{
    public function __construct(
        public readonly string $id,
        #[MapInputName('opportunity.id')]
        public readonly string $opportunity_id,
        #[MapInputName('opportunity.project_name')]
        public readonly ?string $project_name,
        #[MapInputName('opportunity.contractType.type_short_name')]
        public readonly ?string $opportunity_type,
        #[MapInputName('opportunity.importedPrimaryAccount.company_name')]
        public readonly ?string $account_name,
        #[MapInputName('opportunity.accountManager.user_fullname')]
        public readonly ?string $account_manager_name,
        #[MapInputName('opportunity.opportunity_amount')]
        public readonly float $opportunity_amount,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        #[MapInputName('opportunity.opportunity_start_date')]
        public readonly ?\DateTime $opportunity_start_date,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        #[MapInputName('opportunity.opportunity_end_date')]
        public readonly ?\DateTime $opportunity_end_date,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
        #[MapInputName('opportunity.opportunity_closing_date')]
        public readonly ?\DateTime $opportunity_closing_date,
        #[MapInputName('opportunity.pipelineStage.qualified_stage_name')]
        public readonly ?string $sale_action_name,
        #[MapInputName('assignedUser')]
        public readonly ?UserAsRelationData $assigned_user,
        public readonly bool $is_selected,
        public readonly DataAllocationRecordResultEnum $result,
        public readonly ?string $result_reason,
    ) {
    }
}