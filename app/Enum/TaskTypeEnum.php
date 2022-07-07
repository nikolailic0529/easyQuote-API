<?php

namespace App\Enum;

enum TaskTypeEnum: string
{
    case Call = 'Call';
    case Email = 'Email';
    case Task = 'Task';
    case RenewalReminder = 'Renewal Reminder';
}