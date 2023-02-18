<?php

namespace App\Domain\Date\Enum;

enum DateDayEnum: string
{
    case Day1 = 'Day1';
    case Day2 = 'Day2';
    case Day3 = 'Day3';
    case Day4 = 'Day4';
    case Day5 = 'Day5';
    case Day6 = 'Day6';
    case Day7 = 'Day7';
    case Day8 = 'Day8';
    case Day9 = 'Day9';
    case Day10 = 'Day10';
    case Day11 = 'Day11';
    case Day12 = 'Day12';
    case Day13 = 'Day13';
    case Day14 = 'Day14';
    case Day15 = 'Day15';
    case Day16 = 'Day16';
    case Day17 = 'Day17';
    case Day18 = 'Day18';
    case Day19 = 'Day19';
    case Day20 = 'Day20';
    case Day21 = 'Day21';
    case Day22 = 'Day22';
    case Day23 = 'Day23';
    case Day24 = 'Day24';
    case Day25 = 'Day25';
    case Day26 = 'Day26';
    case Day27 = 'Day27';
    case Day28 = 'Day28';
    case Day29 = 'Day29';
    case Day30 = 'Day30';
    case Day31 = 'Day31';

    public function toDayNumber(): int
    {
        return match ($this) {
            self::Day1 => 1,
            self::Day2 => 2,
            self::Day3 => 3,
            self::Day4 => 4,
            self::Day5 => 5,
            self::Day6 => 6,
            self::Day7 => 7,
            self::Day8 => 8,
            self::Day9 => 9,
            self::Day10 => 10,
            self::Day11 => 11,
            self::Day12 => 12,
            self::Day13 => 13,
            self::Day14 => 14,
            self::Day15 => 15,
            self::Day16 => 16,
            self::Day17 => 17,
            self::Day18 => 18,
            self::Day19 => 19,
            self::Day20 => 20,
            self::Day21 => 21,
            self::Day22 => 22,
            self::Day23 => 23,
            self::Day24 => 24,
            self::Day25 => 25,
            self::Day26 => 26,
            self::Day27 => 27,
            self::Day28 => 28,
            self::Day29 => 29,
            self::Day30 => 30,
            self::Day31 => 31,
        };
    }
}
