<?php

namespace App\Models\System;

use Illuminate\Support\Carbon;
use Str;

class Period
{
    /**
     * From Carbon instance.
     *
     * @var \Illuminate\Support\Carbon
     */
    public $from;

    /**
     * Till Carbon instance.
     *
     * @var \Illuminate\Support\Carbon
     */
    public $till;

    /**
     * Formatted Period string.
     *
     * @var string
     */
    public $label;

    public function __construct(string $period)
    {
        ['from' => $from, 'till' => $till] = $this->parsePeriod($period);

        $this->from = $from;
        $this->till = $till;
        $this->label = $this->periodToString($from, $till, $period);
    }

    public static function create(string $period)
    {
        return new static($period);
    }

    protected function parsePeriod(string $period)
    {
        switch ($period) {
            case 'today':
                $from = now()->startOfDay();
                $till = now()->endOfDay();
                break;
            case 'yesterday':
                $from = now()->subDay()->startOfDay();
                $till = now()->subDay()->endOfDay();
                break;
            case 'this_week':
                $from = now()->startOfWeek();
                $till = now()->endOfWeek();
                break;
            case 'last_week':
                $from = now()->startOfWeek()->subWeek()->startOfDay();
                $till = now()->startOfWeek()->subDay()->endOfDay();
                break;
            case 'this_month':
                $from = now()->startOfMonth();
                $till = now()->endOfMonth();
                break;
            case 'last_month':
                $from = now()->startOfMonth()->subMonth();
                $till = now()->startOfMonth()->subDay()->endOfDay();
                break;
            case 'this_year':
                $from = now()->startOfYear();
                $till = now()->endOfYear();
                break;
            default:
                return $from = $till;
                break;
        }

        return compact('from', 'till');
    }

    private function periodToString(Carbon $from, Carbon $till, string $name = 'today')
    {
        $dateFormat = config('date.format_with_time');
        $name = Str::formatAttributeKey($name);

        return $from === $till
            ? "{$name} ({$from->format($dateFormat)})"
            : "{$name} ({$from->format($dateFormat)} — {$till->format($dateFormat)})";
    }
}
