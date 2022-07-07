<?php

namespace App\Integrations\Pipeliner\Models;

class FieldDataSetItemCollection extends BaseInputCollection
{
    public function current(): FieldDataSetItem
    {
        return parent::current();
    }
}