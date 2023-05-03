<?php

namespace App\Domain\User\Events;

use App\Domain\User\Models\User;

final class UserUpdated
{
    public function __construct(
        public readonly User $user,
    ) {
    }
}
