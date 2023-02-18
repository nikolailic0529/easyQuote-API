<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum ReminderStatusEnum: string
{
    case Snoozed = 'Snoozed';
    case Scheduled = 'Scheduled';
    case Dismissed = 'Dismissed';
}
