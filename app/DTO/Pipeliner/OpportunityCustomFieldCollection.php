<?php

namespace App\DTO\Pipeliner;

use Spatie\DataTransferObject\DataTransferObject;

class OpportunityCustomFieldCollection extends DataTransferObject
{
    public readonly ?string $cfNatureOfService1Id;
}