<?php

namespace App\Domain\Task\Enum;

enum TaskTypeEnum: string
{
    case Call = 'Call';
    case Email = 'Email';
    case Task = 'Task';
    case RenewalReminder = 'Renewal Reminder';
}
