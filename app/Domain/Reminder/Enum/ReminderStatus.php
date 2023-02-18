<?php

namespace App\Domain\Reminder\Enum;

enum ReminderStatus: int
{
    case Scheduled = 0;
    case Snoozed = 1;
    case Dismissed = 2;
}
