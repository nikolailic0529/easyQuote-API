<?php

namespace App\Repositories;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Http\Requests\Vendor\{
    StoreVendorRequest,
    UpdateVendorRequest
};
use App\Models\Vendor;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};

class VendorRepository extends SearchableRepository implements VendorRepositoryInterface
{
    protected $vendor;

    public function __construct(Vendor $vendor)
    {
        $this->vendor = $vendor;
    }

    public function allFlatten(): Collection
    {
        return $this->userQuery()->activated()->get()->each->makeHiddenExcept(['id', 'name']);
    }

    public function userQuery(): Builder
    {
        return $this->vendor->query()->with('image', 'countries');
    }

    public function find(string $id): Vendor
    {
        return $this->userQuery()->whereId($id)->firstOrFail()->appendLogo();
    }

    public function create(StoreVendorRequest $request): Vendor
    {
        $user = request()->user();

        $vendor = $user->vendors()->create($request->validated());
        $vendor->createLogo($request->logo);
        $vendor->syncCountries($request->countries);
        $vendor->load('countries');
        $vendor->appendLogo();

        return $vendor;
    }

    public function update(UpdateVendorRequest $request, string $id): Vendor
    {
        $vendor = $this->find($id);

        $vendor->update($request->validated());
        $vendor->createLogo($request->logo);
        $vendor->syncCountries($request->countries);
        $vendor->load('countries');
        $vendor->appendLogo();

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
        return cache()->tags('vendors')->sear("vendors-country:{$id}", function () use ($id) {
            return $this->userQuery()->country($id)->activated()->get();
        });
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\Vendor\OrderByShortCode::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->vendor;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'short_code^4', 'created_at^3'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('image', 'countries');
    }
}
