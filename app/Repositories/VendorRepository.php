<?php

namespace App\Repositories;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Models\Vendor;
use Closure;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};

class VendorRepository extends SearchableRepository implements VendorRepositoryInterface
{
    protected const VENDORS_CACHE_KEY = 'vendors';

    protected Vendor $vendor;

    public function __construct(Vendor $vendor)
    {
        $this->vendor = $vendor;
    }

    public function allFlatten(): Collection
    {
        return $this->userQuery()->activated()->get(['id', 'name']);
    }

    public function allCached()
    {
        return cache()->sear(
            static::VENDORS_CACHE_KEY.'.all',
            fn () => $this->vendor->query()->get(['id', 'name'])->each->setAppends([])
        );
    }

    public function userQuery(): Builder
    {
        return $this->vendor->query()->with('image');
    }

    public function find(string $id): Vendor
    {
        return $this->userQuery()->whereId($id)->firstOrFail()->appendLogo();
    }

    public function findCached(string $id): ?Vendor
    {
        return cache()->sear(
            static::VENDORS_CACHE_KEY . '.' . $id,
            fn () => $this->vendor->whereKey($id)->first()
        );
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

    public function random(int $limit = 1, ?Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->vendor->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof Closure) {
            $scope($query);
        }

        return $query->{$method}();
    }

    public function create($request): Vendor
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return tap($this->vendor->create($request), function (Vendor $vendor) use ($request) {
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
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\Vendor\OrderByShortCode::class,
            \App\Http\Query\DefaultOrderBy::class,
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
