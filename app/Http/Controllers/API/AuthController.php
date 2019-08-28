<?php

namespace App\Http\Controllers\API;

use App\Contracts\Authenticable;
use App\Contracts\AccessLoggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserSignUpRequest;
use App\Http\Requests\UserSignInRequest;
use App\Models\User;
use App\Models\AccessAttempt;

class AuthController extends Controller implements Authenticable, AccessLoggable
{
    public $accessAttempt;
    protected $user;
    protected $tokenResult;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function signup(UserSignUpRequest $request)
    {
        $user = $this->user->make(
            $this->handleSignUpRequest($request)->all(),
        );
        $user->setAsAdmin();
        $user->save();

        return response()->json([
            'message' => __('I have been successfully registered!')
        ], 201);
    }

    public function signin(UserSignInRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $this->storeAccessAttempt($request->all());

        if(!Auth::attempt($credentials)) {
            return response()->json([
                'message' => __('Unauthorized')
            ], 401);
        };

        $this->accessAttempt->markAsSuccessfull();

        $this->generateToken($request);

        return response()->json([
            'access_token' => $this->tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($this->tokenResult->token->expires_at)->toDateTimeString(),
        ]);
    }
    
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => __('You have been successfully logged out.')
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function storeAccessAttempt(Array $payload)
    {
        $this->accessAttempt = new AccessAttempt;
        $this->accessAttempt->setDetails();
        
        return $this->accessAttempt->fill($payload)->save();
    }
    
    private function generateToken(UserSignInRequest $request)
    {
        $user = $request->user();

        $this->tokenResult = $user->createToken('Personal Access Token');
        $token = $this->tokenResult->token;

        if($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }

        $token->save();
    }

    public static function handleSignUpRequest(UserSignUpRequest $request)
    {
        $password = Hash::make($request->password);

        return $request->merge(compact('password'));
    }
}
