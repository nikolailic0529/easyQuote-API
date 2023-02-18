<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum InviteeTypeEnum: string
{
    case Standard = 'Standard';
    case Scheduled = 'Scheduled';
}
