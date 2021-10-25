<?php

namespace App\Services\Auth;

use App\Contracts\{Repositories\UserRepositoryInterface as UserRepositoryInterfaceAlias,
    Services\AuthServiceInterface,};
use App\Enum\Lock;
use App\Models\{AccessAttempt, User,};
use App\Notifications\AttemptsExceeded;
use App\Services\User\UserActivityService;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\{Arr, Facades\Auth};
use Laravel\Passport\PersonalAccessTokenResult;

class AuthService implements AuthServiceInterface
{
    protected int $maxAttempts = 15;

    protected ?AccessAttempt $currentAttempt = null;

    public function __construct(protected UserRepositoryInterfaceAlias $users,
                                protected UserActivityService          $activityService,
                                protected LockProvider                 $lockProvider)
    {
    }

    public function authenticate(array $request)
    {
        $this->checkCredentials(Arr::only($request, ['email', 'password']));

        $token = $this->generateToken($request);

        return $this->response($token);
    }

    public function checkCredentials(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            $this->handleFailedAttempt($credentials);
            return;
        }

        $this->handleSuccessfulAttempt($credentials);
    }

    protected function handleFailedAttempt(array $credentials)
    {
        $user = $this->retrieveUserFromCredentials($credentials);

        if (!is_null($user)) {
            $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getKey()), 10);

            $lock->block(30, function () use ($user) {
                $this->incrementUserFailedAttempts($user);
                $this->deactivateUserWhenAttemptsExceeded($user);
            });
        }

        abort(403, __('auth.failed'));
    }

    protected function retrieveUserFromCredentials(array $credentials): ?User
    {
        $email = Arr::get($credentials, 'email');

        return $this->users->findByEmail($email);
    }

    protected function incrementUserFailedAttempts(?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->users->increment($user->id, 'failed_attempts', ['events' => false, 'timestamps' => false]);
        $user->refresh();
    }

    protected function deactivateUserWhenAttemptsExceeded(?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->failed_attempts < $this->maxAttempts) {
            return;
        }

        $this->users->deactivate($user->getKey());
        $user->notify(new AttemptsExceeded);
    }

    protected function handleSuccessfulAttempt(): void
    {
        /** @var User $user */
        $user = request()->user();

        $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getKey()), 10);

        $lock->block(30, function () use ($user) {
            /**
             * If the User has expired tokens the System will mark the User as Logged Out.
             */
            if ($user->doesntHaveNonExpiredTokens()) {
                $user->markAsLoggedOut();
            }

            /**
             * Once user is logged in,
             * we update the last activity timestamp.
             */
            $user->markAsLoggedIn();
            $this->activityService->updateActivityTimeOfUser($user);

            /**
             * Finally we are resetting user's failed attempts.
             */
            $this->resetUserFailedAttempts($user);
        });

        activity()->on($user)->by($user)->queue('authenticated');
    }

    protected function resetUserFailedAttempts(?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->users->update($user->getKey(), ['failed_attempts' => 0], ['events' => false, 'timestamps' => false]);
    }

    public function generateToken(array $attributes): PersonalAccessTokenResult
    {
        $tokenResult = request()->user()->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        return $tokenResult;
    }

    public function response(PersonalAccessTokenResult $token): array
    {
        $token_type = 'Bearer';
        $access_token = $token->accessToken;
        $expires_at = optional($token->token->expires_at)->toDateTimeString();

        return compact('access_token', 'token_type', 'expires_at');
    }

    public function logout(?User $user = null): bool
    {
        /** @var User $user */
        $user ??= auth()->user();

        $lock = $this->lockProvider->lock(Lock::UPDATE_USER($user->getKey()), 10);

        $lock->block(30, function () use ($user) {
            $user->revokeTokens();
            $user->markAsLoggedOut();
        });

        activity()->on($user)->by($user)->queue('unauthenticated');

        return true;
    }
}
