<?php

namespace App\Integrations\Pipeliner\Models;

class CreateCloudObjectRelationInputCollection extends BaseInputCollection
{
    public function current(): CreateCloudObjectRelationInput
    {
        return parent::current();
    }
}