<?php

namespace App\Domain\Authentication\Services;

use App\Domain\Authentication\Contracts\AuthServiceInterface;
use App\Domain\Authentication\Notifications\AttemptsExceeded;
use App\Domain\Sync\Enum\Lock;
use App\Domain\User\Models\User;
use App\Domain\User\Services\UserActivityService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Arr;
use Laravel\Passport\PersonalAccessTokenFactory;
use Laravel\Passport\PersonalAccessTokenResult;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService implements AuthServiceInterface
{
    protected int $maxAttempts = 15;

    public function __construct(
        protected readonly UserActivityService $activityService,
        protected readonly LockProvider $lockProvider,
        protected readonly UserProvider $userProvider,
        protected readonly PersonalAccessTokenFactory $accessTokenFactory,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function authenticate(array $credentials): array
    {
        $credentials = Arr::only($credentials, ['email', 'password']);

        $user = $this->userProvider->retrieveByCredentials($credentials);

        $this->mustValidateCredentials($user, $credentials);

        $token = $this->generateToken($user);

        return $this->tokenToResponse($token);
    }

    /**
     * @throws \Exception
     */
    public function mustValidateCredentials(?Authenticatable $user, array $credentials): void
    {
        if ($user && $this->userProvider->validateCredentials($user, $credentials)) {
            /* @noinspection PhpParamsInspection */
            $this->handleSuccessfulAttempt($user);
        } else {
            $this->handleFailedAttempt($user);
        }
    }

    /**
     * @throws \Exception
     */
    protected function handleFailedAttempt(?Authenticatable $user): void
    {
        if ($user instanceof User) {
            $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getAuthIdentifier()), 10);

            $lock->block(30, function () use ($user): void {
                $this->incrementUserFailedAttempts($user);
                $this->deactivateUserWhenAttemptsExceeded($user);
            });
        }

        throw new HttpException(403, __('auth.failed'));
    }

    protected function incrementUserFailedAttempts(User $user): void
    {
        User::query()
            ->whereKey($user->getKey())
            ->toBase()
            ->increment('failed_attempts');

        $user->refresh();
    }

    protected function deactivateUserWhenAttemptsExceeded(User $user): void
    {
        if ($user->failed_attempts < $this->maxAttempts) {
            return;
        }

        $user->activated_at = null;
        $user->save();
        $user->notify(new AttemptsExceeded());
    }

    protected function handleSuccessfulAttempt(User $user): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getKey()), 10);

        $lock->block(30, function () use ($user): void {
            /*
             * If the User has expired tokens the System will mark the User as Logged Out.
             */
            if ($user->doesntHaveNonExpiredTokens()) {
                $user->markAsLoggedOut();
            }

            /*
             * Once user is logged in,
             * we update the last activity timestamp.
             */
            $user->markAsLoggedIn();
            $this->activityService->updateActivityTimeOfUser($user);

            /*
             * Finally we are resetting user's failed attempts.
             */
            $this->resetUserFailedAttempts($user);
        });

        activity()->on($user)->by($user)->queue('authenticated');
    }

    protected function resetUserFailedAttempts(User $user): void
    {
        User::query()
            ->whereKey($user->getKey())
            ->toBase()
            ->update(['failed_attempts' => 0]);
    }

    public function generateToken(Authenticatable $user): PersonalAccessTokenResult
    {
        $tokenResult = $this->accessTokenFactory->make($user->getAuthIdentifier(), 'Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        return $tokenResult;
    }

    public function tokenToResponse(PersonalAccessTokenResult $token): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $token->accessToken,
            'expires_at' => $token->token->expires_at?->toDateTimeString(),
        ];
    }

    public function logout(?User $user = null): bool
    {
        /* @var User $user */
        $user ??= auth()->user();

        $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getKey()), 10);

        $lock->block(30, static function () use ($user): void {
            $user->revokeTokens();
            $user->markAsLoggedOut();
        });

        activity()->on($user)->by($user)->queue('unauthenticated');

        return true;
    }
}
