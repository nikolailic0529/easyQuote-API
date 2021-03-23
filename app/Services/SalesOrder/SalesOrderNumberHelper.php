<?php

namespace App\Services\SalesOrder;

class SalesOrderNumberHelper
{
    const COMPANY_PREFIX = 'EPD';

    const PIPELINE_SUFFIX = 'DP';

    public static function makeSalesOrderNumber(string $contractType, int $sequenceNumber): string
    {
        return sprintf(
            "%s-WW-%s-%sSO%'.07d",
            self::COMPANY_PREFIX,
            self::PIPELINE_SUFFIX,
            strtoupper(substr($contractType, 0, 1)),
            $sequenceNumber
        );
    }
}
