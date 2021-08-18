<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CauserAware
{
    public function setCauser(?Model $causer): static;
}