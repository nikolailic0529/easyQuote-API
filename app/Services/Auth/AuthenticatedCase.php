<?php

namespace App\Services\Auth;

use App\Models\{
    User,
    AccessAttempt
};
use App\Notifications\AccessAttempt as AccessAttemptNotification;
use App\Contracts\Repositories\UserRepositoryInterface as Users;

class AuthenticatedCase
{
    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * AccessAttempt instance.
     *
     * @var \App\Models\AccessAttempt
     */
    public AccessAttempt $attempt;

    /**
     * User Repository.
     *
     * @var \App\Contracts\Repositories\UserRepositoryInterface
     */
    public Users $userRepository;

    public function __construct(User $user, AccessAttempt $attempt)
    {
        $this->user = $user;
        $this->attempt = $attempt;
        $this->userRepository = app(Users::class);
    }

    public function notifyUser(): void
    {
        /**
         * We are not notifying user if the attempt is previously known.
         */
        if ($this->attempt->previouslyKnown) {
            return;
        }

        $ip_address = $this->attempt->ip_address;

        $this->user->notify(new AccessAttemptNotification($this->attempt));

        notification()
            ->for($this->user)
            ->message(__(AT_01, compact('ip_address')))
            ->subject($this->user)
            ->priority(3)
            ->store();
    }

    public function abort(string $message, string $code): void
    {
        error_abort($message, $code, 422);
    }

    public static function initiate(User $user, AccessAttempt $attempt): AuthenticatedCase
    {
        return new static($user, $attempt);
    }
}
