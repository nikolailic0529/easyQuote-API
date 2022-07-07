<?php

namespace App\Integrations\Pipeliner\Enum;

enum InviteeTypeEnum: string
{
    case Standard = 'Standard';
    case Scheduled = 'Scheduled';
}