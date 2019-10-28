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
        $this->storeAccessAttempt($request->all());

        $this->checkCredentials($request->only('email', 'password'));

        $this->accessAttempt->markAsSuccessfull();

        $token = $this->generateToken($request);

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
        $this->accessAttempt = $this->accessAttempt->create($payload);
        $this->accessAttempt->setDetails();

        return $this->accessAttempt->save();
    }

    public function generateToken(UserSignInRequest $request): PersonalAccessTokenResult
    {
        $user = $request->user();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();

        return $tokenResult;
    }
}
