<?php

namespace App\Integrations\Pipeliner\Enum;

enum OrgRelationshipTypeEnum
{
    case NoRelationship;
    case Weak;
    case Moderate;
    case Strong;
}