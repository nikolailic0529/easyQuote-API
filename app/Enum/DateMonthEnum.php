<?php

namespace App\Enum;

enum DateMonthEnum: string
{
    case Month1 = 'Month1';
    case Month2 = 'Month2';
    case Month3 = 'Month3';
    case Month4 = 'Month4';
    case Month5 = 'Month5';
    case Month6 = 'Month6';
    case Month7 = 'Month7';
    case Month8 = 'Month8';
    case Month9 = 'Month9';
    case Month10 = 'Month10';
    case Month11 = 'Month11';
    case Month12 = 'Month12';

    public function toMonthNumber(): int
    {
        return match ($this) {
            self::Month1 => 1,
            self::Month2 => 2,
            self::Month3 => 3,
            self::Month4 => 4,
            self::Month5 => 5,
            self::Month6 => 6,
            self::Month7 => 7,
            self::Month8 => 8,
            self::Month9 => 9,
            self::Month10 => 10,
            self::Month11 => 11,
            self::Month12 => 12,
        };
    }
}