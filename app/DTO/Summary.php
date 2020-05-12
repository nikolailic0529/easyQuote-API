<?php

namespace App\DTO;

use Carbon\CarbonPeriod;
use Spatie\DataTransferObject\DataTransferObject;
use Illuminate\Support\Str;

class Summary extends DataTransferObject
{
    protected const VALUE_PRECISION = 2;

    public array $totals;

    public array $period;

    public float $base_rate;

    public string $base_currency;

    public static function create(array $totals, float $base_rate, string $base_currency, ?CarbonPeriod $period): Summary
    {
        $totals = collect($totals)->map(fn ($value, $key) => static::castTotalValue($key, $value, $base_rate))->toArray();

        $period = [
            'start_date' => optional($period, fn ($p) => $p->getStartDate()->toDateString()),
            'end_date' => optional($period, fn ($p) => $p->getEndDate()->toDateString()),
        ];

        return new static(compact(
            'totals',
            'period',
            'base_currency',
            'base_rate'
        ));
    }

    protected static function castTotalValue($key, $value, $base_rate)
    {
        if (Str::contains($key, '_count')) {
            return (int) $value;
        }

        if (Str::contains($key, '_value')) {
            return (float) $value * $base_rate;
        }

        return $value;
    }
}
