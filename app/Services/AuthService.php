<?php

namespace App\Services;

use App\Contracts\{
    Services\AuthServiceInterface,
    Repositories\AccessAttemptRepositoryInterface
};
use App\Exceptions\AlreadyAuthenticatedException;
use Laravel\Passport\PersonalAccessTokenResult;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth, Arr;

class AuthService implements AuthServiceInterface
{
    protected $accessAttempt;

    public function __construct(AccessAttemptRepositoryInterface $accessAttempt)
    {
        $this->accessAttempt = $accessAttempt;
    }

    public function authenticate($request)
    {
        if ($request instanceof Request) {
            $request = $request->validated();
        }

        $attempt = $this->storeAccessAttempt($request);

        $this->checkCredentials(Arr::only($request, ['email', 'password']));

        $token = $this->generateToken($request);

        $attempt->markAsSuccessful($token);

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

        $user = auth()->user();

        /**
         * If the User has expired tokens the System will mark the User as Logged Out.
         */
        $user->doesntHaveNonExpiredTokens() && $user->markAsLoggedOut();

        /**
         * Throw an exception if the User Already Logged In.
         */
        throw_if($user->isAlreadyLoggedIn(), AlreadyAuthenticatedException::class, $user);

        $user->markAsLoggedIn() && $user->freshActivity();
    }

    public function storeAccessAttempt(array $payload)
    {
        return $this->accessAttempt->create($payload);
    }

    public function generateToken(array $attributes): PersonalAccessTokenResult
    {
        $tokenResult = request()->user()->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        return $tokenResult;
    }

    public function logout()
    {
        /**
         * When Logout the System is revoking all the existing User's Personal Access Tokens.
         * Also the User will be marked as Logged Out.
         */
        return auth()->user()->revokeTokens() && auth()->user()->markAsLoggedOut();
    }
}
