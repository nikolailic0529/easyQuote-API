<?php

namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Http\Requests\Company\{
    StoreCompanyRequest,
    UpdateCompanyRequest
};
use App\Http\Resources\CompanyRepositoryCollection;
use App\Models\Company;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};
use Illuminate\Support\Collection as SupportCollection;

class CompanyRepository extends SearchableRepository implements CompanyRepositoryInterface
{
    protected $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function data($additionalData = []): SupportCollection
    {
        $types = __('company.types');
        $categories = __('company.categories');
        $data = collect(compact('types', 'categories'));

        if (!empty($additionalData)) {
            collect($additionalData)->each(function ($array, $key) use ($data) {
                $data->put($key, $array);
            });
        };

        return $data;
    }

    public function all()
    {
        return $this->toCollection(parent::all());
    }

    public function search(string $query = '')
    {
        return $this->toCollection(parent::search($query));
    }

    public function userQuery(): Builder
    {
        return $this->company->query()->with('image', 'vendors', 'addresses.country', 'contacts');
    }

    public function find(string $id): Company
    {
        return $this->userQuery()->whereId($id)->firstOrFail()->withAppends();
    }

    public function create(StoreCompanyRequest $request): Company
    {
        $user = request()->user();

        $company = $user->companies()->create($request->validated());
        $company->createLogo($request->logo);
        $company->syncVendors($request->vendors);
        $company->load('vendors')->appendLogo();

        return $company;
    }

    public function update(UpdateCompanyRequest $request, string $id): Company
    {
        $company = $this->find($id);

        $company->update($request->validated());
        $company->createLogo($request->logo);

        $company->syncVendors($request->vendors);
        $company->syncAddresses($request->addresses_attach);
        $company->detachAddresses($request->addresses_detach);
        $company->syncContacts($request->contacts_attach);
        $company->detachContacts($request->contacts_detach);

        $company->load('vendors', 'addresses', 'contacts')->appendLogo();

        return $company;
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

    protected function toCollection($resource)
    {
        return new CompanyRepositoryCollection($resource);
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\Company\OrderByVat::class,
            \App\Http\Query\Company\OrderByPhone::class,
            \App\Http\Query\Company\OrderByWebsite::class,
            \App\Http\Query\Company\OrderByType::class,
            \App\Http\Query\Company\OrderByCategory::class
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
        return $this->company;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'vat^4', 'category^3', 'type^3', 'email^3', 'phone^3', 'created_at^2'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query->with('image', 'vendors');
    }
}
