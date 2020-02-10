<?php

namespace App\Repositories;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Http\Requests\Vendor\UpdateVendorRequest;
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

    public function findByCode($code)
    {
        $query = $this->vendor->query();

        if (is_string($code)) {
            return $query->whereShortCode($code)->first();
        }

        if (is_array($code)) {
            return $query->whereIn('short_code', $code)->get();
        }

        throw new \InvalidArgumentException(INV_ARG_SA_01);
    }

    public function random(int $limit = 1)
    {
        $method = $limit > 1 ? 'get' : 'first';

        return $this->vendor->query()->inRandomOrder()->limit($limit)->{$method}();
    }

    public function create($request): Vendor
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return tap($this->vendor->create($request), function ($vendor) use ($request) {
            $vendor->createLogo(data_get($request, 'logo'));
            $vendor->syncCountries(data_get($request, 'countries'));
            $vendor->load('countries');
            $vendor->appendLogo();
        });
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
