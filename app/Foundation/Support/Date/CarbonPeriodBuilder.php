<?php

namespace App\Foundation\Support\Date;

use Carbon\CarbonPeriod;

class CarbonPeriodBuilder
{
    public static function buildFromPeriodName(string $periodName): CarbonPeriod
    {
        return match ($periodName) {
            'today' => CarbonPeriod::createFromArray([
                now()->startOfDay(),
                now()->endOfDay(),
            ]),
            'yesterday' => CarbonPeriod::createFromArray([
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
            ]),
            'this_week' => CarbonPeriod::createFromArray([
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]),
            'last_week' => CarbonPeriod::createFromArray([
                now()->startOfWeek()->subWeek()->startOfDay(),
                now()->startOfWeek()->subDay()->endOfDay(),
            ]),
            'this_month' => CarbonPeriod::createFromArray([
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]),
            'last_month' => CarbonPeriod::createFromArray([
                now()->startOfMonth()->subMonth(),
                now()->startOfMonth()->subDay()->endOfDay(),
            ]),
            'this_year' => CarbonPeriod::createFromArray([
                now()->startOfYear(),
                now()->endOfYear(),
            ]),
        };
    }

    public static function isValidPeriodName(string $periodName): bool
    {
        try {
            self::buildFromPeriodName($periodName);

            return true;
        } catch (\UnhandledMatchError $e) {
            return false;
        }
    }
}
