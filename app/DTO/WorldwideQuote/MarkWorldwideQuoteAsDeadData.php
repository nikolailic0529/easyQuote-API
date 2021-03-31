<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class MarkWorldwideQuoteAsDeadData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $status_reason;
}
