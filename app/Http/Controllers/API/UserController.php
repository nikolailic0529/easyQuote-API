<?php

namespace App\Http\Controllers\API;

use App\Casts\UserGrantedPermission;
use App\Contracts\Repositories\{
    UserRepositoryInterface as UserRepository,
    RoleRepositoryInterface as RoleRepository,
    CountryRepositoryInterface as CountryRepository,
    TimezoneRepositoryInterface as TimezoneRepository
};
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\{
    Collaboration\InviteUserRequest,
    Collaboration\UpdateUserRequest,

    StoreResetPasswordRequest,
    User\ListByRoles,
};
use App\Http\Resources\User\UserByRoleCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\ProfileHelper;

class UserController extends Controller
{
    protected $user;

    public function __construct(UserRepository $user, RoleRepository $role, CountryRepository $country, TimezoneRepository $timezone)
    {
        $this->user = $user;
        $this->role = $role;
        $this->country = $country;
        $this->timezone = $timezone;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        return response()->json(
            request()->filled('search')
                ? $this->user->search(request('search'))
                : $this->user->all()
        );
    }

    /**
     * Display a list of users.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        return response()->json(
            $this->user->list()
        );
    }

    /**
     * Display an exclusive listing of users.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exclusiveList(Request $request)
    {
        return response()->json(
            $this->user->exclusiveList(auth()->id())
        );
    }

    /**
     * Display a listing of users with specific roles.
     *
     * @param ListByRoles $request
     * @return \Illuminate\Http\Response
     */
    public function listByRoles(ListByRoles $request)
    {
        $resource = $this->user->findByRoles(
            $request->roles,
            fn (Builder $q) => $q->withCasts(['granted_level' => UserGrantedPermission::class . ':' . $request->granted_module])
        );

        return response()->json(
            UserByRoleCollection::make($resource)
        );
    }

    /**
     * Data for creating a new Role.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->json(
            [
                'roles' => $this->role->allActivated(['id', 'name']),
                'countries' => $this->country->all(),
                'timezones' => $this->timezone->all()
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return response()->json(
            $this->user->find($user->id)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Collaboration\InviteUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function store(InviteUserRequest $request)
    {
        $this->authorize('create', User::class);

        return response()->json(
            $this->user->invite($request)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Collaboration\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('updateProfile', [$user, $request]);

        $resource = ProfileHelper::listenAndFlushUserProfile($user, fn () => $this->user->update($user->id, $request->validated()));

        return response()->json(
            $resource
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        return response()->json(
            $this->user->delete($user->id)
        );
    }

    /**
     * Activate the specified Role from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function activate(User $user)
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->user->activate($user->id)
        );
    }

    /**
     * Deactivate the specified Role from storage.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function deactivate(User $user)
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->user->deactivate($user->id)
        );
    }

    /**
     * Initiate Reset Password for specified User.
     *
     * @param StoreResetPasswordRequest $request
     * @param User $user
     * @return void
     */
    public function resetPassword(StoreResetPasswordRequest $request, User $user)
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->user->resetPassword($request, $user->id)
        );
    }

    public function resetAccount(User $user)
    {
        $this->authorize('update', $user);

        return response()->json(
            $this->user->resetAccount($user->id)
        );
    }
}
