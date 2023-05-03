<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum InviteeResponseEnum: string
{
    case NoResponse = 'NoResponse';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
}
