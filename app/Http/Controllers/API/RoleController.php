<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\RoleRepositoryInterface as Roles;
use App\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\{
    ShowForm,
    StoreRoleRequest,
    UpdateRoleRequest
};
use App\Http\Resources\Role\Role as RoleResource;
use App\Http\Resources\Role\RoleListing;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProfileHelper;

class RoleController extends Controller
{
    protected Roles $roles;

    public function __construct(Roles $roles)
    {
        $this->roles = $roles;
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            request()->filled('search')
                ? $this->roles->search(request('search'))
                : $this->roles->all()
        );
    }

    /**
     * Display the roles having minimal access to the module.
     *
     * @param string $module
     * @return void
     */
    public function module(string $module)
    {
        $resource = $this->roles->findByModule($module, fn (Builder $builder) => $builder->withCount('users'));

        return response()->json(
            RoleListing::collection($resource)
        );
    }

    /**
     * Data for creating a new Role.
     *
     * @param  ShowForm $request
     * @return \Illuminate\Http\Response
     */
    public function create(ShowForm $request)
    {
        return response()->json(
            $request->data()
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
            RoleResource::make(
                $this->roles->create($request->validated())->load('companies:id,name')
            )
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
            RoleResource::make($role->load('companies:id,name'))
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
        $resource = tap(
            $this->roles->update($role->getKey(), $request->validated()),
            fn (Role $role) => ProfileHelper::flushRoleUserProfiles($role)
        );

        return response()->json(
            RoleResource::make($resource->load('companies:id,name'))
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
            $this->roles->delete($role->id)
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
            $this->roles->activate($role->id)
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
            $this->roles->deactivate($role->id)
        );
    }
}
