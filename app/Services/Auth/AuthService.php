<?php

namespace App\Services\Auth;

use App\Contracts\{Repositories\UserRepositoryInterface as Users, Services\AuthServiceInterface,};
use App\Models\{AccessAttempt, User,};
use App\Notifications\AttemptsExceeded;
use App\Repositories\UserRepository;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\{Arr, Facades\Auth};
use Laravel\Passport\PersonalAccessTokenResult;

class AuthService implements AuthServiceInterface
{
    public int $maxAttempts = 15;

    public bool $checkIp = true;

    protected ?AccessAttempt $currentAttempt = null;

    protected Users $users;

    public function __construct(Users $users)
    {
        $this->users = $users;
    }

    public function disableCheckIp(): AuthServiceInterface
    {
        $this->checkIp = false;

        return $this;
    }

    public function authenticate(array $request)
    {
        // $this->currentAttempt = $this->attempts->retrieveOrCreate($request);

        $this->checkCredentials(Arr::only($request, ['email', 'password']));

        $token = $this->generateToken($request);

        // $this->currentAttempt->markAsSuccessful($token);

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
            UserRepository::lock($user->getKey(), 10)->block(30, function () use ($user) {
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

        $this->users->deactivate($user->id);
        $user->notify(new AttemptsExceeded);
    }

    protected function handleSuccessfulAttempt()
    {
        /** @var \App\Models\User */
        $user = request()->user();

        UserRepository::lock($user->getKey(), 10)->block(30, function () use ($user) {
            /**
             * If the User has expired tokens the System will mark the User as Logged Out.
             */
            if ($user->doesntHaveNonExpiredTokens()) {
                $user->markAsLoggedOut();
            }

            /**
             * Throw an exception if the User Already Logged In.
             */
            // $this->checkAlreadyAuthenticatedCase($user);

            /**
             * Once user logged in we are freshing the last activity timestamp and writing the related activity.
             */
            // $user->markAsLoggedIn($this->currentAttempt->ip);
            $user->markAsLoggedIn();
            $user->freshActivity();

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

        $this->users->update($user->id, ['failed_attempts' => 0], ['events' => false, 'timestamps' => false]);
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

    public function logout(?User $user = null)
    {
        /** @var \App\Models\User */
        $user ??= auth()->user();

        UserRepository::lock($user->getKey(), 10)->block(30, function () use ($user) {
            $user->revokeTokens();
            $user->markAsLoggedOut();
        });

        activity()->on($user)->by($user)->queue('unauthenticated');

        return true;
    }

    protected function checkAlreadyAuthenticatedCase(User $user): void
    {
        app(Pipeline::class)
            ->send(app('auth.case')->initiate($user, $this->currentAttempt))
            ->through([
                \App\Services\Auth\AuthenticatedCases\LoggedInDifferentAccount::class,
                \App\Services\Auth\AuthenticatedCases\AlreadyLoggedIn::class,
            ])
            ->thenReturn();
    }
}
