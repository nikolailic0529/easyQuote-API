<?php

namespace App\Domain\Authorization\Controllers\V1;

use App\Domain\Authorization\Contracts\RoleRepositoryInterface as Roles;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Requests\ShowFormRequest;
use App\Domain\Authorization\Requests\StoreRoleBaseRequest;
use App\Domain\Authorization\Requests\UpdateRoleBaseRequest;
use App\Domain\Authorization\Resources\V1\Role as RoleResource;
use App\Domain\Authorization\Resources\V1\RoleListing;
use App\Foundation\Http\Controller;
use Illuminate\Database\Eloquent\Builder;

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
     * @return \Illuminate\Http\Response
     */
    public function create(ShowFormRequest $request)
    {
        return response()->json(
            $request->data()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleBaseRequest $request)
    {
        return response()->json(
            RoleResource::make(
                $this->roles->create($request->validated())
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        return response()->json(
            RoleResource::make($role)
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoleBaseRequest $request, Role $role)
    {
        $resource = $this->roles->update($role->getKey(), $request->validated());

        return response()->json(
            RoleResource::make($resource)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
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
