<?php

namespace App\DTO;

use Illuminate\Http\Resources\PotentiallyMissing;

class MissingValue implements PotentiallyMissing
{
    public function isMissing(): bool
    {
        return true;
    }
}