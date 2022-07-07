<?php

namespace App\Integrations\Pipeliner\Enum;

enum ReminderStatusEnum: string
{
    case Snoozed = 'Snoozed';
    case Scheduled = 'Scheduled';
    case Dismissed = 'Dismissed';
}