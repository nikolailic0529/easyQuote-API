<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SetStageOfOpportunityData extends DataTransferObject
{
    #[Constraints\PositiveOrZero]
    public int $order_in_stage;

    #[Constraints\Uuid]
    public string $stage_id;
}
