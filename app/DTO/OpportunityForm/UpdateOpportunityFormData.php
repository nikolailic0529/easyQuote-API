<?php

namespace App\DTO\OpportunityForm;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateOpportunityFormData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $pipeline_id;
}
