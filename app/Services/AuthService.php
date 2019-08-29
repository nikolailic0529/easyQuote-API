<?php

namespace App\Services;


use Auth, Hash;
use Carbon\Carbon;
use App\Contracts\Repositories\AccessAttemptRepositoryInterface;
use App\Http\Requests \ {
    UserSignUpRequest,
    UserSignInRequest
};
use App\Contracts\Services\AuthServiceInterface;

class AuthService implements AuthServiceInterface
{
    public $accessAttempt;

    public function __construct(AccessAttemptRepositoryInterface $accessAttempt)
    {
        $this->accessAttempt = $accessAttempt;
    }

    public function checkCredentials(Array $credentials)
    {
        return Auth::attempt($credentials);
    }

    public function storeAccessAttempt(Array $payload)
    {
        $this->accessAttempt = $this->accessAttempt->create(
            $payload
        );
        $this->accessAttempt->setDetails();
        
        return $this->accessAttempt->save();
    }
    
    public function parseTokenTime($tokenResult)
    {
        return Carbon::parse($tokenResult->token->expires_at)->toDateTimeString();
    }
    
    public function generateToken(UserSignInRequest $request)
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

    public static function handleSignUpRequest(UserSignUpRequest $request)
    {
        $password = Hash::make($request->password);
    
        return $request->merge(compact('password'));
    }
}