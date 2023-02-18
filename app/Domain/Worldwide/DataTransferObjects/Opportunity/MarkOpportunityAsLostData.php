<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class MarkOpportunityAsLostData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $status_reason;
}
