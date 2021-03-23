<?php

namespace App\Enum;

final class SalesOrderStatus extends Enum
{
    const
        QUEUE = 0,
        SENT = 1,
        FAILURE = 2,
        CANCEL = 3;
}
