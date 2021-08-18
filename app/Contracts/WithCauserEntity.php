<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface WithCauserEntity
{
    public function getCauser(): ?Model;
}