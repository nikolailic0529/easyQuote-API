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
                $from = $till = now();
                break;
            case 'yesterday':
                $from = now()->subDay();
                $till = now();
                break;
            case 'this_week':
                $from = now()->startOfWeek();
                $till = now()->endOfWeek();
                break;
            case 'last_week':
                $from = now()->startOfWeek()->subWeek();
                $till = now()->startOfWeek()->subDay();
                break;
            case 'this_month':
                $from = now()->startOfMonth();
                $till = now()->endOfMonth();
                break;
            case 'last_month':
                $from = now()->startOfMonth()->subMonth();
                $till = now()->startOfMonth()->subDay();
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
        $dateFormat = config('date.format');
        $name = Str::formatAttributeKey($name);

        return $from === $till
            ? "{$name} {$from->format($dateFormat)}"
            : "{$name} ({$from->format($dateFormat)} — {$till->format($dateFormat)})";
    }
}
