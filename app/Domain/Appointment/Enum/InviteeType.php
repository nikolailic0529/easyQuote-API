<?php

namespace App\Domain\Appointment\Enum;

enum InviteeType: int
{
    case Standard = 0;
    case Scheduled = 1;
}
