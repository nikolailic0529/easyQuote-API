<?php

namespace App\Enum;

enum InviteeResponse: int
{
    case NoResponse = 0;
    case Accepted = 1;
    case Rejected = 2;
}