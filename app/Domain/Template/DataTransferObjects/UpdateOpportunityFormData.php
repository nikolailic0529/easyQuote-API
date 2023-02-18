<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateOpportunityFormData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $pipeline_id;
}
