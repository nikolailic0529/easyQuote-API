<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum ActivityStatusEnum: string
{
    case NotStarted = 'NotStarted';
    case InProgress = 'InProgress';
    case Waiting = 'Waiting';
    case Completed = 'Completed';
    case Deferred = 'Deferred';
}
