<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

class OpportunityCustomFieldCollection extends DataTransferObject
{
    public readonly ?string $cfNatureOfService1Id;
}
