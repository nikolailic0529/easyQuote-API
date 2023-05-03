<?php

namespace App\Domain\Task\Enum;

enum TaskStatusEnum: string
{
    case NotStarted = 'Not Started';
    case InProgress = 'In Progress';
    case Waiting = 'Waiting';
    case Completed = 'Completed';
    case Deferred = 'Deferred';
}
