<?php

namespace App\Domain\Date\Enum;

use Carbon\CarbonInterface;

enum DayOfWeekEnum: int
{
    case Sunday = 1 << 0;
    case Monday = 1 << 1;
    case Tuesday = 1 << 2;
    case Wednesday = 1 << 3;
    case Thursday = 1 << 4;
    case Friday = 1 << 5;
    case Saturday = 1 << 6;

    public function isPresentInMask(int $mask): bool
    {
        return ($mask & $this->value) === $this->value;
    }

    public function toDayOfWeekNumber(): int
    {
        return match ($this) {
            self::Sunday => CarbonInterface::SUNDAY,
            self::Monday => CarbonInterface::MONDAY,
            self::Tuesday => CarbonInterface::TUESDAY,
            self::Wednesday => CarbonInterface::WEDNESDAY,
            self::Thursday => CarbonInterface::THURSDAY,
            self::Friday => CarbonInterface::FRIDAY,
            self::Saturday => CarbonInterface::SATURDAY,
        };
    }
}
