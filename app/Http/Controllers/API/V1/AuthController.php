<?php

namespace App\Http\Controllers\API\V1;

use App\Contracts\{Repositories\UserRepositoryInterface, Services\AuthServiceInterface};
use App\Contracts\Repositories\System\BuildRepositoryInterface;
use App\DTO\User\UpdateCurrentUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\{Auth\LogoutUser, PasswordResetRequest, UserSignInRequest};
use App\Http\Resources\{V1\AuthenticatedUserResource,
    V1\Invitation\InvitationPublicResource,
    V1\User\AttemptsResource,
    V1\User\AuthResource};
use App\Models\{PasswordReset};
use App\Services\User\UserEntityService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $user,
        protected AuthServiceInterface $auth
    ) {
    }

    /**
     * Show specified Invitation
     *
     * @param  string  $invitation
     * @return JsonResponse
     */
    public function showInvitation(string $invitation): JsonResponse
    {
        return response()->json(
            InvitationPublicResource::make($this->user->invitation($invitation))
        );
    }

    /**
     * Authenticate specified User
     *
     * @param  UserSignInRequest  $request
     * @return JsonResponse
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
     * Logout authenticated User
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        return response()->json(
            $this->auth->logout()
        );
    }

    /**
     * Authenticate user with username & password and logout.
     *
     * @param  LogoutUser  $request
     * @return JsonResponse
     */
    public function authenticateAndLogout(LogoutUser $request): JsonResponse
    {
        return response()->json(
            $this->auth->logout($request->getLogoutableUser())
        );
    }

    /**
     * Get authenticated User
     *
     * @param  BuildRepositoryInterface  $build
     * @return JsonResponse
     */
    public function user(BuildRepositoryInterface $build): JsonResponse
    {
        return response()->json(
            AuthenticatedUserResource::make(
                auth()->user()->load('companies:id,name', 'hpeContractTemplate:id,name', 'roles.companies:id,name')
            )
                ->additional(['build' => $build->last()])
        );
    }

    /**
     * Show failed access attempts by specific user.
     *
     * @param  string  $email
     * @return JsonResponse
     */
    public function showAttempts(string $email): JsonResponse
    {
        return response()->json(
            AttemptsResource::make($this->user->findByEmail($email))
        );
    }

    /**
     * Update current user.
     *
     * @param  UpdateCurrentUserData  $data
     * @param  UserEntityService  $service
     * @param  BuildRepositoryInterface  $build
     * @return JsonResponse
     */
    public function updateCurrentUser(
        Guard $guard,
        UpdateCurrentUserData $data,
        UserEntityService $service,
        BuildRepositoryInterface $build
    ): JsonResponse {
        /** @noinspection PhpParamsInspection */
        return response()->json(
            AuthenticatedUserResource::make(
                $service
                    ->updateCurrentUser($guard->user(), $data)
                    ->load('companies:id,name', 'hpeContractTemplate:id,name', 'roles.companies:id,name')
                    ->withAppends()
            )
                ->additional(['build' => $build->last()])
        );
    }

    /**
     * Perform Reset Password.
     *
     * @param  PasswordResetRequest  $request
     * @param  PasswordReset  $reset
     * @return JsonResponse
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
     *
     * @param  string  $reset
     * @return JsonResponse
     */
    public function verifyPasswordReset(string $reset): JsonResponse
    {
        return response()->json(
            $this->user->verifyPasswordReset($reset)
        );
    }
}
