<?php

namespace App\DTO\Tasks;

use App\Enum\ReminderStatus;
use DateTimeImmutable;
use Spatie\DataTransferObject\DataTransferObject;

final class CreateTaskReminderData extends DataTransferObject
{
    public DateTimeImmutable $set_date;
    public ReminderStatus $status;
}