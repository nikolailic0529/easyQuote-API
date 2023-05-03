<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum EntityLacotaType
{
    case Account;
    case Contact;
    case Task;
    case Appointment;
    case Lead;
    case Opportunity;
    case Project;
}
