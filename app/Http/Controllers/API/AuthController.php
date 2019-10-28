<?php namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests \ {
    UserSignUpRequest,
    UserSignInRequest,
    Collaboration\CompleteInvitationRequest
};
use App\Contracts \ {
    Repositories\UserRepositoryInterface,
    Services\AuthServiceInterface
};
use App\Models\Collaboration\Invitation;

class AuthController extends Controller
{
    protected $user;

    protected $auth;

    public function __construct(UserRepositoryInterface $user, AuthServiceInterface $auth)
    {
        $this->user = $user;
        $this->auth = $auth;
    }

    public function signup(UserSignUpRequest $request)
    {
        return response()->json(
            (bool) $this->user->create($request->validated())
        );
    }

    public function invitation(Invitation $invitation)
    {
        return response()->json(
            $this->user->invitation($invitation->invitation_token)
        );
    }

    public function completeInvitation(CompleteInvitationRequest $request, Invitation $invitation)
    {
        return response()->json(
            (bool) $this->user->completeInvitation($request->validated(), $invitation)
        );
    }

    public function signin(UserSignInRequest $request)
    {
        return response()->json(
            $this->auth->authenticate($request)
        );
    }

    public function logout()
    {
        return response()->json(
            request()->user()->token()->revoke()
        );
    }

    public function user()
    {
        return response()->json(
            request()->user()
        );
    }
}
