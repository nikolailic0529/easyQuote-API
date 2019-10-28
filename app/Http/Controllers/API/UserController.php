<?php namespace App\Http\Controllers\API;

use App\Contracts\Repositories\UserRepositoryInterface as UserRepository;
use App\Models \ {
    User,
    Collaboration\Invitation
};
use App\Http\Controllers\Controller;
use App\Http\Requests\Collaboration \ {
    InviteUserRequest,
    UpdateUserRequest
};

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
        //
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    /**
     * Activate the specified Role from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function activate(User $user)
    {
        //
    }

    /**
     * Deactivate the specified Role from storage.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function deactivate(User $user)
    {
        //
    }
}
