<?php

namespace App\Domain\Pipeliner\Integration\Models;

class UpdateAccountInputCollection extends BaseInputCollection
{
    public function current(): UpdateAccountInput
    {
        return parent::current();
    }
}
