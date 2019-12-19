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
    protected $attemptRepository;

    protected $currentAttempt;

    public $checkIp = true;

    public function __construct(AccessAttemptRepository $attemptRepository)
    {
        $this->attemptRepository = $attemptRepository;
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

        $this->currentAttempt = $this->storeAccessAttempt($request);

        $this->checkCredentials(Arr::only($request, ['email', 'password']));

        $token = $this->generateToken($request);

        $this->currentAttempt->markAsSuccessful($token);

        return $this->response($token);
    }

    public function response(PersonalAccessTokenResult $token): array
    {
        $token_type = 'Bearer';
        $access_token = $token->accessToken;
        $expires_at = Carbon::parse($token->token->expires_at)->toDateTimeString();

        return compact('access_token', 'token_type', 'expires_at');
    }

    public function checkCredentials(array $credentials)
    {
        Auth::attempt($credentials) || abort(401, __('auth.failed'));

        $user = request()->user();

        /**
         * If the User has expired tokens the System will mark the User as Logged Out.
         */
        $user->doesntHaveNonExpiredTokens() && $user->markAsLoggedOut();

        /**
         * Throw an exception if the User Already Logged In.
         */
        $this->checkAlreadyAuthenticatedCase($user);

        /**
         * Once user logged in we are freshing the last activity timestamp and writing the related activity.
         */
        $user->markAsLoggedIn($this->currentAttempt->ip) && $user->freshActivity();

        activity()->on($user)->by($user)->log('authenticated');
    }

    public function storeAccessAttempt(array $payload)
    {
        return $this->attemptRepository->create($payload);
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
        $user = $user instanceof User ? $user : auth()->user();

        /**
         * When Logout the System is revoking all the existing User's Personal Access Tokens.
         * Also the User will be marked as Logged Out.
         */
        return $user->revokeTokens() && $user->markAsLoggedOut();
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