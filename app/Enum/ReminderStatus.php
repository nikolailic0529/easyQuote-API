<?php

namespace App\Enum;

enum ReminderStatus: int
{
    case Scheduled = 0;
    case Snoozed = 1;
    case Dismissed = 2;
}