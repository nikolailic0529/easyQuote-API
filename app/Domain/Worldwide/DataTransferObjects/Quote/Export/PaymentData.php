<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class PaymentData extends DataTransferObject
{
    public string $from;

    public string $to;

    public string $price;
}
