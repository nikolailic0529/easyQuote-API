<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\RoleRepositoryInterface as RoleRepository;
use App\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\{
    StoreRoleRequest,
    UpdateRoleRequest
};

class RoleController extends Controller
{
    protected $role;

    public function __construct(RoleRepository $role)
    {
        $this->role = $role;
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->filled('search')) {
            return response()->json(
                $this->role->search(request('search'))
            );
        }

        return response()->json(
            $this->role->all()
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
            $this->role->data()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Role\StoreRoleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        return response()->json(
            $this->role->create($request)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return response()->json(
            $this->role->find($role->id)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Role\UpdateRoleRequest  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        return response()->json(
            $this->role->update($request, $role->id)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        return response()->json(
            $this->role->delete($role->id)
        );
    }

    /**
     * Activate the specified Role from storage.
     *
     * @param  \App\Models\Role $role
     * @return \Illuminate\Http\Response
     */
    public function activate(Role $role)
    {
        $this->authorize('update', $role);

        return response()->json(
            $this->role->activate($role->id)
        );
    }

    /**
     * Deactivate the specified Role from storage.
     *
     * @param  \App\Models\Role $role
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Role $role)
    {
        $this->authorize('update', $role);

        return response()->json(
            $this->role->deactivate($role->id)
        );
    }
}
