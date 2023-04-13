<?php

namespace App\Domain\Maintenance\Enum;

enum MaintenanceStatusEnum
{
    case Stopped;
    case Scheduled;
    case Running;
}
