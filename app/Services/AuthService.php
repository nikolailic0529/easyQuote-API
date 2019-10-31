<?php namespace App\Services;

use App\Contracts \ {
    Services\AuthServiceInterface,
    Repositories\AccessAttemptRepositoryInterface
};
use App\Http\Requests\UserSignInRequest;
use Laravel\Passport\PersonalAccessTokenResult;
use Carbon\Carbon;
use Auth;

class AuthService implements AuthServiceInterface
{
    protected $accessAttempt;

    public function __construct(AccessAttemptRepositoryInterface $accessAttempt)
    {
        $this->accessAttempt = $accessAttempt;
    }

    public function authenticate(UserSignInRequest $request)
    {
        $attempt = $this->storeAccessAttempt($request->validated());

        $this->checkCredentials($request->only('email', 'password'));

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
        return Auth::attempt($credentials) || abort(401, __('Unauthorized'));
    }

    public function storeAccessAttempt(array $payload)
    {
        return $this->accessAttempt->create($payload);
    }

    public function generateToken(UserSignInRequest $request): PersonalAccessTokenResult
    {
        $tokenResult = $request->user()->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if((bool) $request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();

        return $tokenResult;
    }
}
