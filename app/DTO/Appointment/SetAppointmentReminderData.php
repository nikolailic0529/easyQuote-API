<?php

namespace App\DTO\Appointment;

use App\Enum\ReminderStatus;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Symfony\Component\Validator\Constraints;

final class SetAppointmentReminderData extends Data
{
    #[Constraints\PositiveOrZero]
    public int|Optional $start_date_offset;

    #[WithCast(DateTimeInterfaceCast::class, type: \DateTimeImmutable::class, format: 'Y-m-d H:i:s')]
    public \DateTimeImmutable|Optional $snooze_date;

    public ReminderStatus|Optional $status;
}