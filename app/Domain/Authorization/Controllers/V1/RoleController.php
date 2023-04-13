<?php

namespace App\Domain\Authorization\Controllers\V1;

use App\Domain\Authorization\Models\Role;
use App\Domain\Authorization\Queries\RoleQueries;
use App\Domain\Authorization\Requests\CreateRoleRequest;
use App\Domain\Authorization\Requests\ShowFormRequest;
use App\Domain\Authorization\Requests\UpdateRoleRequest;
use App\Domain\Authorization\Resources\V1\RoleListResource;
use App\Domain\Authorization\Resources\V1\RoleResource;
use App\Domain\Authorization\Services\RoleEntityService;
use App\Domain\Authorization\Services\RolePresenter;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    public function __construct()
    {
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
     * List roles satisfy the module permissions.
     */
    public function module(
        Request $request,
        RoleQueries $queries,
        string $module
    ): JsonResponse {
        $collection = $queries->rolesSatisfyModuleQuery($module)->get();

        return response()->json(
            RoleListResource::collection($collection)
        );
    }

    /**
     * Show form data.
     */
    public function create(ShowFormRequest $request): JsonResponse
    {
        return response()->json(
            $request->data()
        );
    }

    /**
     * Create role.
     */
    public function store(
        CreateRoleRequest $request,
        RolePresenter $presenter,
        RoleEntityService $service
    ): JsonResponse {
        $role = $service->setCauser($request->user())
            ->createRole($request->getCreateRoleData());

        return response()->json(
            RoleResource::make($role)
                ->additional([
                    'privileges' => $presenter->presentModules($role),
                    'properties' => $presenter->presentProperties($role),
                ])
        );
    }

    /**
     * Show role.
     */
    public function show(RolePresenter $presenter, Role $role): JsonResponse
    {
        return response()->json(
            RoleResource::make($role)
                ->additional([
                    'privileges' => $presenter->presentModules($role),
                    'properties' => $presenter->presentProperties($role),
                ])
        );
    }

    /**
     * Update role.
     */
    public function update(
        UpdateRoleRequest $request,
        RolePresenter $presenter,
        RoleEntityService $service,
        Role $role
    ): JsonResponse {
        $service->setCauser($request->user())
            ->updateRole($role, $request->getUpdateRoleData());

        return response()->json(
            RoleResource::make($role)
                ->additional([
                    'privileges' => $presenter->presentModules($role),
                    'properties' => $presenter->presentProperties($role),
                ])
        );
    }

    /**
     * Delete role.
     */
    public function destroy(
        Request $request,
        RoleEntityService $service,
        Role $role
    ): Response {
        $service->setCauser($request->user())
            ->deleteRole($role);

        return response()->noContent();
    }

    /**
     * Mark role as active.
     *
     * @throws AuthorizationException
     */
    public function activate(
        Request $request,
        RoleEntityService $service,
        Role $role
    ): Response {
        $this->authorize('update', $role);

        $service->setCauser($request->user())
            ->markRoleAsActive($role);

        return response()->noContent();
    }

    /**
     * Mark role as inactive.
     *
     * @throws AuthorizationException
     */
    public function deactivate(
        Request $request,
        RoleEntityService $service,
        Role $role
    ): Response {
        $this->authorize('update', $role);

        $service->setCauser($request->user())
            ->markRoleAsInactive($role);

        return response()->noContent();
    }
}
