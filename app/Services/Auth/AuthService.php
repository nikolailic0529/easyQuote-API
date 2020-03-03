<?php

namespace App\Services\Auth;

use App\Contracts\{
    Services\AuthServiceInterface,
    Repositories\AccessAttemptRepositoryInterface as AccessAttemptRepository
};
use App\Models\User;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth, Arr;

class AuthService implements AuthServiceInterface
{
    /** @var boolean */
    public $checkIp = true;

    /** @var \App\Contracts\Repositories\AccessAttemptRepositoryInterface */
    protected $attempt;

    /** @var \App\Models\AccessAttempt */
    protected $currentAttempt;

    public function __construct(AccessAttemptRepository $attempt)
    {
        $this->attempt = $attempt;
    }

    public function disableCheckIp(): AuthServiceInterface
    {
        $this->checkIp = false;

        return $this;
    }

    public function authenticate($request)
    {
        if ($request instanceof Request) {
            $request = $request->validated();
        }

        $this->currentAttempt = $this->attempt->retrieveOrCreate($request);

        $this->checkCredentials(Arr::only($request, ['email', 'password']));

        $token = $this->generateToken($request);

        $this->currentAttempt->markAsSuccessful($token);

        return $this->response($token);
    }

    public function response(PersonalAccessTokenResult $token): array
    {
        $token_type = 'Bearer';
        $access_token = $token->accessToken;
        $expires_at = optional($token->token->expires_at)->toDateTimeString();

        return compact('access_token', 'token_type', 'expires_at');
    }

    public function checkCredentials(array $credentials)
    {
        abort_unless(Auth::attempt($credentials), 403, __('auth.failed'));

        $user = request()->user();

        /**
         * If the User has expired tokens the System will mark the User as Logged Out.
         */
        if ($user->doesntHaveNonExpiredTokens()) {
            $user->markAsLoggedOut();
        }

        /**
         * Throw an exception if the User Already Logged In.
         */
        $this->checkAlreadyAuthenticatedCase($user);

        /**
         * Once user logged in we are freshing the last activity timestamp and writing the related activity.
         */
        $user->markAsLoggedIn($this->currentAttempt->ip) && $user->freshActivity();

        activity()->on($user)->by($user)->queue('authenticated');
    }

    public function generateToken(array $attributes): PersonalAccessTokenResult
    {
        $tokenResult = request()->user()->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        return $tokenResult;
    }

    public function logout(?User $user = null)
    {
        $user ??= auth()->user();

        /**
         * When Logout the System is revoking all the existing User's Personal Access Tokens.
         * Also the User will be marked as Logged Out.
         */
        $pass = $user->revokeTokens() && $user->markAsLoggedOut();

        activity()->on($user)->by($user)->queue('deauthenticated');

        return $pass;
    }

    protected function checkAlreadyAuthenticatedCase(User $user): void
    {
        app(Pipeline::class)
            ->send(app('auth.case')->initiate($user, $this->currentAttempt))
            ->through([
                \App\Services\Auth\AuthenticatedCases\AlreadyLoggedIn::class,
                \App\Services\Auth\AuthenticatedCases\LoggedInDifferentAccount::class
            ])
            ->thenReturn();
    }
}
