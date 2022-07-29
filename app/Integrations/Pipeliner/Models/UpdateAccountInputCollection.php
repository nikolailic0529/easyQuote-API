<?php

namespace App\Integrations\Pipeliner\Models;

class UpdateAccountInputCollection extends BaseInputCollection
{
    public function current(): UpdateAccountInput
    {
        return parent::current();
    }
}