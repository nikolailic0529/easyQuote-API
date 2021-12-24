<?php

namespace App\Contracts;

use App\Models\User;

interface ActingUserAware
{
    public function setActingUser(?User $user): static;
}