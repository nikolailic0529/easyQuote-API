<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class PaymentData extends DataTransferObject
{
    public string $from;

    public string $to;

    public string $price;
}
