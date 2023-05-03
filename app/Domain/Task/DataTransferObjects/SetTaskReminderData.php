<?php

namespace App\Domain\Task\DataTransferObjects;

use App\Domain\Reminder\Enum\ReminderStatus;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class SetTaskReminderData extends Data
{
    public \DateTimeImmutable|Optional $set_date;
    public ReminderStatus|Optional $status;
}
