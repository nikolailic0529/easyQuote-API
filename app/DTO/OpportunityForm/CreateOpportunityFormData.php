<?php

namespace App\DTO\OpportunityForm;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateOpportunityFormData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $pipeline_id;
}
