<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\UserRepositoryInterface as UserRepository;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration\{
    InviteUserRequest,
    UpdateUserRequest
};
use App\Http\Requests\StoreResetPasswordRequest;

class UserController extends Controller
{
    protected $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        if (request()->filled('search')) {
            return response()->json(
                $this->user->search(request('search'))
            );
        }

        return response()->json(
            $this->user->all()
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
            $this->user->data()
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
        $this->authorize('update', $user);

        return response()->json(
            $this->user->update($request, $user->id)
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
}
