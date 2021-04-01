<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    UserSignUpRequest,
    UserSignInRequest,
    Collaboration\CompleteInvitationRequest,
    PasswordResetRequest,
    UpdateProfileRequest,
    Auth\LogoutUser
};
use App\Contracts\{
    Repositories\UserRepositoryInterface,
    Services\AuthServiceInterface
};
use App\Contracts\Repositories\System\BuildRepositoryInterface;
use App\Http\Resources\{
    AuthenticatedUserResource,
    User\AttemptsResource,
    User\AuthResource,
    Invitation\InvitationPublicResource
};
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
     * Show specified Invitation
     *
     * @param string $invitation
     * @return \Illuminate\Http\Response
     */
    public function showInvitation(string $invitation)
    {
        return response()->json(
            InvitationPublicResource::make($this->user->invitation($invitation))
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
        $response = $this->auth->authenticate($request->validated());

        return response()->json(
            AuthResource::make($response)->additional([
                'recaptcha_response' => request('recaptcha_response')
            ])
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
     * Authenticate user with username & password and logout.
     *
     * @param  LogoutUser $request
     * @return \Illuminate\Http\Response
     */
    public function authenticateAndLogout(LogoutUser $request)
    {
        return response()->json(
            $this->auth->logout($request->getLogoutableUser())
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
            AuthenticatedUserResource::make(
                auth()->user()->load('company:id,name', 'hpeContractTemplate:id,name', 'roles.companies:id,name')
            )
                ->additional(['build' => $build->last()])
        );
    }

    /**
     * Show failed access attempts by specific user.
     *
     * @param string $email
     * @return \Illuminate\Http\Response
     */
    public function showAttempts(string $email)
    {
        return response()->json(
            AttemptsResource::make($this->user->findByEmail($email))
        );
    }

    /**
     * Update Current User's Profile.
     *
     * @param UpdateProfileRequest $request
     * @return \Illuminate\Http\Response
     */
    public function updateOwnProfile(UpdateProfileRequest $request, BuildRepositoryInterface $build)
    {
        return response()->json(
            AuthenticatedUserResource::make(
                $this->user->updateOwnProfile($request)->load('company:id,name', 'hpeContractTemplate:id,name', 'roles.companies:id,name')
            )
                ->additional(['build' => $build->last()])
        );
    }

    /**
     * Perform Reset Password.
     *
     * @param ResetPassword $reset
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function verifyPasswordReset(string $reset)
    {
        return response()->json(
            $this->user->verifyPasswordReset($reset)
        );
    }
}
