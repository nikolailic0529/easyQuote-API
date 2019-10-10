<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests \ {
    UserSignUpRequest,
    UserSignInRequest
};
use App\Contracts \ {
    Authenticable,
    Repositories\UserRepositoryInterface,
    Services\AuthServiceInterface
};

class AuthController extends Controller implements Authenticable
{
    protected $user;
    protected $tokenResult;
    protected $authService;

    public function __construct(UserRepositoryInterface $user, AuthServiceInterface $authService)
    {
        $this->user = $user;
        $this->authService = $authService;
    }

    public function signup(UserSignUpRequest $request)
    {
        $user = $this->user->make(
            $this->authService->handleSignUpRequest($request)->all()
        );
        $user->save();

        return response()->json([
            'message' => __('You have been successfully registered!')
        ], 201);
    }

    public function signin(UserSignInRequest $request)
    {
        $this->authService->storeAccessAttempt($request->all());

        $credentials = $request->only('email', 'password');

        if(!$this->authService->checkCredentials($credentials)) {
            return response()->json([
                'message' => __('Unauthorized')
            ], 401);
        };

        $this->authService->accessAttempt->markAsSuccessfull();

        $this->setToken($request);

        return response()->json([
            'access_token' => $this->tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $this->authService->parseTokenTime($this->tokenResult),
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

    public function setToken(UserSignInRequest $request)
    {
        $this->tokenResult = $this->authService->generateToken($request);
    }
}
