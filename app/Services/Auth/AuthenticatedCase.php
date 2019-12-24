<?php

namespace App\Services\Auth;

use App\Models\{
    User,
    AccessAttempt
};
use App\Notifications\AccessAttempt as AttemptMail;

class AuthenticatedCase
{
    /**
     * User instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * AccessAttempt instance.
     *
     * @var \App\Models\AccessAttempt
     */
    public $attempt;

    /**
     * User Repository.
     *
     * @var \App\Contracts\Repositories\UserRepositoryInterface
     */
    public $userRepository;

    public function __construct(User $user, AccessAttempt $attempt)
    {
        $this->user = $user;
        $this->attempt = $attempt;
        $this->userRepository = app('user.repository');
    }

    public function notifyUser(): void
    {
        $this->user->notify(new AttemptMail($this->attempt));
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
