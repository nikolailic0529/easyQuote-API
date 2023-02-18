<?php

namespace App\Domain\Authentication\Services;

use App\Domain\Authentication\Models\{
    AccessAttempt
};
use App\Domain\Authentication\Notifications\AccessAttempt as AccessAttemptNotification;
use App\Domain\User\Contracts\UserRepositoryInterface as Users;
use App\Domain\User\Models\User;
use Illuminate\Http\Response;

class AuthenticatedCase
{
    /**
     * User instance.
     */
    public User $user;

    /**
     * AccessAttempt instance.
     *
     * @var \App\Domain\Authentication\Models\AccessAttempt
     */
    public AccessAttempt $attempt;

    /**
     * User Repository.
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
        /*
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
            ->push();
    }

    public function abort(string $message, string $code, array $headers = []): void
    {
        error_abort($message, $code, Response::HTTP_UNPROCESSABLE_ENTITY, $headers);
    }

    public static function initiate(User $user, AccessAttempt $attempt): AuthenticatedCase
    {
        return new static($user, $attempt);
    }
}
