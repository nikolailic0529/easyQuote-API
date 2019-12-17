<?php

namespace App\Services;

use App\Contracts\Services\ReportLoggerInterface;

class ReportLogger implements ReportLoggerInterface
{
    public function log(): void
    {
        $arguments = collect(func_get_args());

        $response = $arguments->mapWithKeys(function ($value, $key) {
            return [$key => $value];
        });

        $response->shift();
    }
}
