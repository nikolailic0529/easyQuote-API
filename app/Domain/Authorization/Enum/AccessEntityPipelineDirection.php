<?php

namespace App\Domain\Authorization\Enum;

enum AccessEntityPipelineDirection: string
{
    case Selected = 'selected';
    case All = 'all';
}
