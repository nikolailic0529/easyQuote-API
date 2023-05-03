<?php

namespace App\Domain\Pipeliner\Integration\Enum;

enum OrgRelationshipTypeEnum
{
    case NoRelationship;
    case Weak;
    case Moderate;
    case Strong;
}
