<?php

namespace App\Integrations\Pipeliner\Enum;

enum ActivityStatusEnum: string
{
    case NotStarted = 'NotStarted';
    case InProgress = 'InProgress';
    case Waiting = 'Waiting';
    case Completed = 'Completed';
    case Deferred = 'Deferred';
}