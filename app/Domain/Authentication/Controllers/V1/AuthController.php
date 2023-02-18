<?php

namespace App\Domain\Authentication\Controllers\V1;

use App\Domain\Authentication\Contracts\AuthServiceInterface;
use App\Domain\Authentication\Models\PasswordReset;
use App\Domain\Authentication\Requests\LogoutUserRequest;
use App\Domain\Authentication\Requests\PasswordResetRequest;
use App\Domain\Authentication\Requests\ShowCurrentUserRequest;
use App\Domain\Authentication\Requests\UserSignInRequest;
use App\Domain\Invitation\Resources\V1\InvitationPublicResource;
use App\Domain\User\Contracts\UserRepositoryInterface;
use App\Domain\User\DataTransferObjects\UpdateCurrentUserData;
use App\Domain\User\Resources\V1\AttemptsResource;
use App\Domain\User\Resources\V1\AuthenticatedUserResource;
use App\Domain\User\Resources\V1\AuthResource;
use App\Domain\User\Services\UserEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $user,
        protected AuthServiceInterface $auth
    ) {
    }

    /**
     * Show specified Invitation.
     */
    public function showInvitation(string $invitation): JsonResponse
    {
        return response()->json(
            InvitationPublicResource::make($this->user->invitation($invitation))
        );
    }

    /**
     * Authenticate specified User.
     */
    public function signin(UserSignInRequest $request): JsonResponse
    {
        $response = $this->auth->authenticate($request->validated());

        return response()->json(
            AuthResource::make($response)->additional([
                'recaptcha_response' => request('recaptcha_response'),
            ])
        );
    }

    /**
     * Logout authenticated User.
     */
    public function logout(): JsonResponse
    {
        return response()->json(
            $this->auth->logout()
        );
    }

    /**
     * Authenticate user with username & password and logout.
     */
    public function authenticateAndLogout(LogoutUserRequest $request): JsonResponse
    {
        return response()->json(
            $this->auth->logout($request->getLogoutableUser())
        );
    }

    /**
     * Show current authenticated user.
     */
    public function showCurrentUser(
        ShowCurrentUserRequest $request,
    ): JsonResponse {
        return response()->json(
            AuthenticatedUserResource::make($request->user())
                ->additional($request->getAdditional())
        );
    }

    /**
     * Show failed access attempts by specific user.
     */
    public function showAttempts(string $email): JsonResponse
    {
        return response()->json(
            AttemptsResource::make($this->user->findByEmail($email))
        );
    }

    /**
     * Update current user.
     */
    public function updateCurrentUser(
        ShowCurrentUserRequest $request,
        UpdateCurrentUserData $data,
        UserEntityService $service,
    ): JsonResponse {
        return response()->json(
            AuthenticatedUserResource::make(
                $service->updateCurrentUser($request->user(), $data)
            )
                ->additional($request->getAdditional())
        );
    }

    /**
     * Perform Reset Password.
     *
     * @param \App\Domain\Authentication\Models\PasswordReset $reset
     */
    public function resetPassword(PasswordResetRequest $request, PasswordReset $reset): JsonResponse
    {
        return response()->json(
            $this->user->performResetPassword($request, $reset->token)
        );
    }

    /**
     * Verify the specified PasswordReset Token.
     * Returns False if is expired or doesn't exist.
     * Returns True if the token isn't expired and exists.
     */
    public function verifyPasswordReset(string $reset): JsonResponse
    {
        return response()->json(
            $this->user->verifyPasswordReset($reset)
        );
    }
}
