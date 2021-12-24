<?php

namespace App\DTO;

use Carbon\CarbonPeriod;
use Spatie\DataTransferObject\DataTransferObject;

class Summary extends DataTransferObject
{
    protected const VALUE_PRECISION = 2;

    public array $totals;

    public array $period;

    public float $base_rate;

    public string $base_currency;

    public static function fromArrayOfTotals(array $totals, float $baseRateValue, string $baseCurrency, ?CarbonPeriod $period): Summary
    {
        $totals = collect($totals)->map(function ($value, $key) use ($baseRateValue) {
            return static::castTotalValue($key, $value, $baseRateValue);
        })->all();

        $period = [
            'start_date' => transform($period, fn(CarbonPeriod $p) => $p->getStartDate()->toDateString()),
            'end_date' => transform($period, fn(CarbonPeriod $p) => $p->getEndDate()->toDateString()),
        ];

        return new static([
            'totals' => $totals,
            'period' => $period,
            'base_currency' => $baseCurrency,
            'base_rate' => $baseRateValue
        ]);
    }

    protected static function castTotalValue($key, $value, $base_rate)
    {
        if (str_contains($key, '_count')) {
            return (int)$value;
        }

        if (str_contains($key, '_value')) {
            return round((float)$value * $base_rate, 2);
        }

        return $value;
    }
}
