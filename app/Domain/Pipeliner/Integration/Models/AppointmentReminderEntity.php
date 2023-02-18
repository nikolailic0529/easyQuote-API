<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\ReminderStatusEnum;
use Illuminate\Support\Carbon;

class AppointmentReminderEntity
{
    public function __construct(
        public readonly string $id,
        public readonly int $endDateOffset,
        public readonly ?\DateTimeImmutable $snoozeDate,
        public readonly ReminderStatusEnum $status
    ) {
    }

    public static function tryFromArray(?array $array): ?static
    {
        if (is_null($array)) {
            return null;
        }

        return static::fromArray($array);
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            endDateOffset: $array['endDateOffset'],
            snoozeDate: static::parseDateTime($array['snoozeDate']),
            status: ReminderStatusEnum::from($array['status']),
        );
    }

    private static function parseDateTime(?string $dateTimeStr): ?\DateTimeImmutable
    {
        if (is_null($dateTimeStr)) {
            return null;
        }

        return Carbon::parse($dateTimeStr)->toDateTimeImmutable();
    }
}
