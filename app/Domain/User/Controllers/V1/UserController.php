<?php

namespace App\Domain\User\Controllers\V1;

use App\Domain\Authentication\Requests\{StoreResetPasswordRequest};
use App\Domain\Invitation\DataTransferObjects\CreateInvitationData;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\Invitation\Requests\CompleteInvitationRequest;
use App\Domain\Invitation\Services\InvitationEntityService;
use App\Domain\User\Casts\UserGrantedPermission;
use App\Domain\User\Contracts\{UserRepositoryInterface as UserRepository};
use App\Domain\User\DataTransferObjects\UpdateUserData;
use App\Domain\User\Models\User;
use App\Domain\User\Queries\UserQueries;
use App\Domain\User\Requests\ListByRolesRequest;
use App\Domain\User\Requests\ShowFormRequest;
use App\Domain\User\Resources\V1\UserByRoleCollection;
use App\Domain\User\Resources\V1\UserWithIncludes;
use App\Domain\User\Services\UserEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $user)
    {
        $this->userRepository = $user;
    }

    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function paginateUsers(Request $request, UserQueries $userQueries): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $paginator = $userQueries->paginateUsersQuery($request)->apiPaginate();

        return response()->json(
            \App\Domain\User\Resources\V1\UserRepositoryCollection::make($paginator)
        );
    }

    /**
     * Show list of users.
     * Allowed filters: team_id, business_division_id.
     */
    public function list(Request $request, UserQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->userListQuery($request)->get()
        );
    }

    /**
     * Display an exclusive listing of users.
     */
    public function exclusiveList(Request $request): JsonResponse
    {
        return response()->json(
            $this->userRepository->exclusiveList(auth()->id())
        );
    }

    /**
     * Display a listing of users with specific roles.
     */
    public function listByRoles(ListByRolesRequest $request): JsonResponse
    {
        $resource = $this->userRepository->findByRoles(
            $request->roles,
            fn (Builder $q
            ) => $q->withCasts(['granted_level' => UserGrantedPermission::class.':'.$request->granted_module])
        );

        return response()->json(
            UserByRoleCollection::make($resource)
        );
    }

    /**
     * Data for creating a new Role.
     */
    public function showUserFormData(ShowFormRequest $request): JsonResponse
    {
        return response()->json(
            $request->data()
        );
    }

    /**
     * Show the existing User entity.
     *
     * @throws AuthorizationException
     */
    public function showUser(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(
            UserWithIncludes::make($user)
        );
    }

    /**
     * Create a new invitation entity and send the invitation email.
     *
     * @throws AuthorizationException
     */
    public function inviteUser(
        CreateInvitationData $data,
        InvitationEntityService $invitationEntityService
    ): JsonResponse {
        $this->authorize('create', User::class);

        $invitation = $invitationEntityService->createInvitation($data);

        return response()->json(
            $invitation,
            Response::HTTP_CREATED,
        );
    }

    /**
     * Update user.
     *
     * @throws AuthorizationException
     */
    public function updateUser(
        UpdateUserData $data,
        UserEntityService $service,
        User $user
    ): JsonResponse {
        $this->authorize('updateProfile', $user);

        $service->updateUser($user, $data);

        return response()->json($user);
    }

    /**
     * Delete the specified User entity.
     *
     * @throws AuthorizationException
     */
    public function destroyUser(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        return response()->json(
            $this->userRepository->delete($user->getKey())
        );
    }

    /**
     * Mark as active the specified User entity.
     *
     * @throws AuthorizationException
     */
    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->userRepository->activate($user->getKey())
        );
    }

    /**
     * Mark as inactive the specified User entity.
     *
     * @throws AuthorizationException
     */
    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->userRepository->deactivate($user->getKey())
        );
    }

    /**
     * Perform password reset of the specified User entity.
     *
     * @throws AuthorizationException
     */
    public function resetPassword(StoreResetPasswordRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->userRepository->resetPassword($request, $user->getKey())
        );
    }

    /**
     * Mark as logged out the specified User entity.
     *
     * @throws AuthorizationException
     */
    public function resetAccount(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->userRepository->resetAccount($user->getKey())
        );
    }

    /**
     * Register a new user entity.
     */
    public function registerUser(
        CompleteInvitationRequest $request,
        UserEntityService $userEntityService,
        Invitation $invitation
    ): JsonResponse {
        return response()->json(
            $userEntityService->registerUser(invitation: $invitation, userData: $request->getRegisterUserData()),
            Response::HTTP_CREATED,
        );
    }
}
