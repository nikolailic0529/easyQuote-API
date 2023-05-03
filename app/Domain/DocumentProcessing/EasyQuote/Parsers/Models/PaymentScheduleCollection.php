<?php

namespace App\Domain\DocumentProcessing\EasyQuote\Parsers\Models;

use Illuminate\Support\Collection;

final class PaymentScheduleCollection extends Collection
{
    public function offsetGet($key): PaymentScheduleData
    {
        return parent::offsetGet($key);
    }
}
