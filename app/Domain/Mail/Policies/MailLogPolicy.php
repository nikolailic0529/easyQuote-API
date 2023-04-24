<?php

namespace App\Domain\Mail\Policies;

use App\Domain\Mail\Models\MailLog;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class MailLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }

    public function view(User $user, MailLog $mailLog): Response
    {
        if ($user->hasRole(R_SUPER)) {
            return $this->allow();
        }

        return $this->deny();
    }
}
