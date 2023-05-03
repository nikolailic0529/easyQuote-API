<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PaymentScheduleField extends DataTransferObject
{
    /**
     * @Constraints\Choice({"from", "to", "price"})
     */
    public string $field_name;

    /**
     * @Constraints\NotBlank
     */
    public string $field_header;
}
