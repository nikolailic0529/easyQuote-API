<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateCloudObjectRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateCloudObjectRelationInput
    {
        return parent::current();
    }
}
