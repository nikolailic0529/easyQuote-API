<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\ReminderStatusEnum;
use Illuminate\Support\Carbon;

class TaskReminderEntity
{
    public function __construct(
        public readonly string $id,
        public readonly \DateTimeImmutable $setDate,
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
            setDate: static::parseDateTime($array['setDate']),
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
