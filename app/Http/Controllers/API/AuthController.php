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

    /**
     * Register a new User with Administrator Role
     *
     * @param UserSignUpRequest $request
     * @return \Illuminate\Http\Response
     */
    public function signup(UserSignUpRequest $request)
    {
        $this->user->createAdministrator($request->validated());

        return response()->json(
            $this->auth->authenticate($request)
        );
    }

    /**
     * Show specified Invitation
     *
     * @param Invitation $invitation
     * @return \Illuminate\Http\Response
     */
    public function invitation(Invitation $invitation)
    {
        return response()->json(
            $this->user->invitation($invitation->invitation_token)
        );
    }

    /**
     * Register a new Collaboration User with Invitation specified Role and Collaboration
     *
     * @param CompleteInvitationRequest $request
     * @param Invitation $invitation
     * @return \Illuminate\Http\Response
     */
    public function signupCollaborator(CompleteInvitationRequest $request, Invitation $invitation)
    {
        $this->user->createCollaborator($request->validated(), $invitation);

        return response()->json(
            $this->auth->authenticate($request->merge(['email' => $invitation->email])->all())
        );
    }

    /**
     * Authenticate specified User
     *
     * @param UserSignInRequest $request
     * @return \Illuminate\Http\Response
     */
    public function signin(UserSignInRequest $request)
    {
        return response()->json(
            $this->auth->authenticate($request)
        );
    }

    /**
     * Logout authenticated User
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        return response()->json(
            request()->user()->token()->revoke()
        );
    }

    /**
     * Get authenticated User
     *
     * @return \Illuminate\Http\Response
     */
    public function user()
    {
        return response()->json(
            request()->user()
        );
    }
}
