<?php

namespace App\Domain\Authentication\Contracts;

use Illuminate\Database\Eloquent\Model;

interface WithCauserEntity
{
    public function getCauser(): ?Model;
}
