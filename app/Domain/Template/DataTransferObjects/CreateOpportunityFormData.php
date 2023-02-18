<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateOpportunityFormData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $pipeline_id;
}
