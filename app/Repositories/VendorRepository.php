<?php namespace App\Repositories;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Builder\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Vendor \ {
    StoreVendorRequest,
    UpdateVendorRequest
};
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository extends SearchableRepository implements VendorRepositoryInterface
{
    protected $vendor;

    public function __construct(Vendor $vendor)
    {
        parent::__construct();
        $this->vendor = $vendor;
    }

    public function all(): Paginator
    {
        return $this->filterQuery($this->userQuery())->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'short_code^4', 'created_at^3'
        ];

        $items = $this->searchOnElasticsearch($this->vendor, $searchableFields, $query);

        $query = $this->buildQuery($this->vendor, $items, function ($query) {
            return $query->where(function ($query) {
                return $query->currentUser();
            })->with('image', 'countries');
        });

        return $query->apiPaginate();
    }

    public function userQuery(): Builder
    {
        return $this->vendor->query()->currentUser()->with('image', 'countries');
    }

    public function find(string $id): Vendor
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StoreVendorRequest $request): Vendor
    {
        $user = request()->user();

        $vendor = $user->vendors()->create($request->validated());
        $vendor->createImage($request->logo);
        $vendor->syncCountries($request->countries);
        $vendor->load('countries');

        return $vendor;
    }

    public function update(UpdateVendorRequest $request, string $id): Vendor
    {
        $vendor = $this->find($id);

        $vendor->update($request->validated());
        $vendor->createImage($request->logo);
        $vendor->syncCountries($request->countries);
        $vendor->load('countries');

        return $vendor;
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

    public function country(string $id): Collection
    {
        return $this->userQuery()->country($id)->activated()->get();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\Vendor\OrderByName::class,
            \App\Http\Query\Vendor\OrderByShortCode::class,
            \App\Http\Query\DefaultGroupByActivation::class
        ];
    }
}
