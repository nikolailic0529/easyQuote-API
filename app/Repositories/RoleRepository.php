<?php namespace App\Repositories;

use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Models \ {
    Role,
    Permission
};
use App\Http\Requests\Role \ {
    StoreRoleRequest,
    UpdateRoleRequest
};
use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RoleRepository extends SearchableRepository implements RoleRepositoryInterface
{
    protected $role;

    public function __construct(Role $role, Permission $permission)
    {
        parent::__construct();
        $this->role = $role;
        $this->permission = $permission;
    }

    public function data(): Collection
    {
        $privileges = __('role.privileges');
        $modules = collect(__('role.modules'))->keys();

        return collect(compact('privileges', 'modules'));
    }

    public function all(): Paginator
    {
        $activated = $this->filterQuery($this->userQuery()->activated());
        $deactivated = $this->filterQuery($this->userQuery()->deactivated());

        return $activated->union($deactivated)->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'created_at'
        ];

        $items = $this->searchOnElasticsearch($this->role, $searchableFields, $query);

        $activated = $this->buildQuery($this->role, $items, function ($query) {
            $query->userCollaboration()->activated();
            $this->filterQuery($query);
        });
        $deactivated = $this->buildQuery($this->role, $items, function ($query) {
            $query->userCollaboration()->deactivated();
            $this->filterQuery($query);
        });

        return $activated->union($deactivated)->apiPaginate();
    }

    public function userQuery(): Builder
    {
        return $this->role->query()->userCollaboration();
    }

    public function find(string $id): Role
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StoreRoleRequest $request): Role
    {
        return $this->role->create($request->validated())->syncPrivileges();
    }

    public function update(UpdateRoleRequest $request, string $id): Role
    {
        $role = $this->find($id);
        $role->update($request->validated());

        return $role;
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\Role\OrderByName::class,
            \App\Http\Query\OrderByCreatedAt::class
        ];
    }
}
