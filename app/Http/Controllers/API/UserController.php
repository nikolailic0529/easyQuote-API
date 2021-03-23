<?php

namespace App\Http\Controllers\API;

use App\Casts\UserGrantedPermission;
use App\Http\Resources\UserRepositoryCollection;
use App\Queries\UserQueries;
use App\Contracts\Repositories\{UserRepositoryInterface as UserRepository,};
use App\Http\Controllers\Controller;
use App\Http\Requests\{Collaboration\InviteUserRequest,
    Collaboration\UpdateUserRequest,
    StoreResetPasswordRequest,
    User\ListByRoles,
    User\ShowForm,};
use App\Http\Resources\User\UserByRoleCollection;
use App\Http\Resources\User\UserWithIncludes;
use App\Models\User;
use App\Services\ProfileHelper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * @param Request $request
     * @param UserQueries $userQueries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function paginateUsers(Request $request, UserQueries $userQueries): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $paginator = $userQueries->paginateUsersQuery($request)->apiPaginate();

        return response()->json(
            UserRepositoryCollection::make($paginator)
        );
    }

    /**
     * Display a list of users.
     *
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        return response()->json(
            $this->userRepository->list()
        );
    }

    /**
     * Display an exclusive listing of users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exclusiveList(Request $request): JsonResponse
    {
        return response()->json(
            $this->userRepository->exclusiveList(auth()->id())
        );
    }

    /**
     * Display a listing of users with specific roles.
     *
     * @param ListByRoles $request
     * @return JsonResponse
     */
    public function listByRoles(ListByRoles $request): JsonResponse
    {
        $resource = $this->userRepository->findByRoles(
            $request->roles,
            fn(Builder $q) => $q->withCasts(['granted_level' => UserGrantedPermission::class.':'.$request->granted_module])
        );

        return response()->json(
            UserByRoleCollection::make($resource)
        );
    }

    /**
     * Data for creating a new Role.
     *
     * @param ShowForm $request
     * @return JsonResponse
     */
    public function showUserFormData(ShowForm $request): JsonResponse
    {
        return response()->json(
            $request->data()
        );
    }

    /**
     * Show the existing User entity.
     *
     * @param User $user
     * @return JsonResponse
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
     * Update the specified resource in storage.
     *
     * @param InviteUserRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function inviteUser(InviteUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        return response()->json(
            $this->userRepository->invite($request)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateUser(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('updateProfile', [$user, $request]);

        $resource = ProfileHelper::listenAndFlushUserProfile($user,
            function () use ($user, $request) {
                return $this->userRepository->update($user->getKey(),
                    $request->validated()
                );
            });

        return response()->json(
            $resource
        );
    }

    /**
     * Delete the specified User entity.
     *
     * @param User $user
     * @return JsonResponse
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
     * @param User $user
     * @return JsonResponse
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
     * @param User $user
     * @return JsonResponse
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
     * @param StoreResetPasswordRequest $request
     * @param User $user
     * @return JsonResponse
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
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function resetAccount(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->userRepository->resetAccount($user->getKey())
        );
    }
}
