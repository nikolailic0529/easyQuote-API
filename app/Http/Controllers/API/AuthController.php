<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    UserSignUpRequest,
    UserSignInRequest,
    Collaboration\CompleteInvitationRequest,
    PasswordResetRequest,
    RefreshTokenRequest,
    UpdateProfileRequest
};
use App\Contracts\{
    Repositories\UserRepositoryInterface,
    Services\AuthServiceInterface
};
use App\Contracts\Repositories\System\BuildRepositoryInterface;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\{
    Collaboration\Invitation,
    PasswordReset
};

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
     * @param string $invitation
     * @return \Illuminate\Http\Response
     */
    public function showInvitation(string $invitation)
    {
        return response()->json(
            $this->user->invitation($invitation)
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
        return response()->json(
            $this->user->createCollaborator($request->validated(), $invitation)
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
            $this->auth->logout()
        );
    }

    /**
     * Get authenticated User
     *
     * @return \Illuminate\Http\Response
     */
    public function user(BuildRepositoryInterface $build)
    {
        return response()->json(
            AuthenticatedUserResource::make(auth()->user())
                ->additional(['build' => $build->latest()])
        );
    }

    /**
     * Update Current User's Profile.
     *
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\Response
     */
    public function updateOwnProfile(UpdateProfileRequest $request)
    {
        return response()->json(
            $this->user->updateOwnProfile($request)
        );
    }

    /**
     * Perform Reset Password.
     *
     * @param ResetPassword $reset
     * @return void
     */
    public function resetPassword(PasswordResetRequest $request, PasswordReset $reset)
    {
        return response()->json(
            $this->user->performResetPassword($request, $reset->token)
        );
    }

    /**
     * Verify the specified PasswordReset Token.
     * Returns False if is expired or doesn't exist.
     * Returns True if the token isn't expired and exists.
     *
     * @param string $reset
     * @return void
     */
    public function verifyPasswordReset(string $reset)
    {
        return response()->json(
            $this->user->verifyPasswordReset($reset)
        );
    }
}
