<?php

namespace App\Domain\Note\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface HasOwnNotes
{
    public function notes(): MorphToMany;
}
