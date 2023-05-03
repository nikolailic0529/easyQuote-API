<?php

namespace App\Domain\Worldwide\Enum;

use App\Foundation\Support\Enum\Enum;

final class SalesOrderStatus extends Enum
{
    const QUEUE = 0;
    const SENT = 1;
    const FAILURE = 2;
    const CANCEL = 3;
}
