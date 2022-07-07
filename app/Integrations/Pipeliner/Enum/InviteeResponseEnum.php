<?php

namespace App\Integrations\Pipeliner\Enum;

enum InviteeResponseEnum: string
{
    case NoResponse = 'NoResponse';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
}