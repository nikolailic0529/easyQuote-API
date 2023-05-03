<?php

namespace App\Domain\Pipeliner\Integration\Models;

class FieldDataSetItemCollection extends BaseInputCollection
{
    public function current(): FieldDataSetItem
    {
        return parent::current();
    }
}
