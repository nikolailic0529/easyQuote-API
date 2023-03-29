<?php

namespace App\Domain\Authorization\Controllers\V1;

use App\Domain\Authorization\Contracts\RoleRepositoryInterface as Roles;
use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Queries\RoleQueries;
use App\Domain\Authorization\Requests\ShowFormRequest;
use App\Domain\Authorization\Requests\StoreRoleBaseRequest;
use App\Domain\Authorization\Requests\UpdateRoleBaseRequest;
use App\Domain\Authorization\Resources\V1\Role as RoleResource;
use App\Domain\Authorization\Resources\V1\RoleListing;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected readonly Roles $roles
    ) {
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Paginate roles.
     */
    public function index(Request $request, RoleQueries $queries): JsonResponse
    {
        return response()->json(
            $queries->paginateRolesQuery($request)->apiPaginate()
        );
    }

    /**
     * Show the roles having access to the module.
     *
     * @param string $module
     * @return JsonResponse
     */
    public function module(string $module): JsonResponse
    {
        $resource = $this->roles->findByModule($module,
            static fn (Builder $builder) => $builder->withCount('users'));

        return response()->json(
            RoleListing::collection($resource)
        );
    }

    /**
     * Show form data.
     *
     * @param ShowFormRequest $request
     * @return JsonResponse
     */
    public function create(ShowFormRequest $request): JsonResponse
    {
        return response()->json(
            $request->data()
        );
    }

    /**
     * Create role.
     *
     * @param StoreRoleBaseRequest $request
     * @return JsonResponse
     */
    public function store(StoreRoleBaseRequest $request): JsonResponse
    {
        return response()->json(
            RoleResource::make(
                $this->roles->create($request->validated())
            )
        );
    }

    /**
     * Show role.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json(
            RoleResource::make($role)
        );
    }

    /**
     * Update role.
     *
     * @param UpdateRoleBaseRequest $request
     * @param Role                  $role
     * @return JsonResponse
     */
    public function update(UpdateRoleBaseRequest $request, Role $role): JsonResponse
    {
        $resource = $this->roles->update($role->getKey(), $request->validated());

        return response()->json(
            RoleResource::make($resource)
        );
    }

    /**
     * Delete role.
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function destroy(Role $role): JsonResponse
    {
        return response()->json(
            $this->roles->delete($role->getKey())
        );
    }

    /**
     * Mark role as active.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function activate(Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        return response()->json(
            $this->roles->activate($role->getKey())
        );
    }

    /**
     * Mark role as inactive.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deactivate(Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        return response()->json(
            $this->roles->deactivate($role->getKey())
        );
    }
}
