<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class MarkWorldwideQuoteAsDeadData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $status_reason;
}
