<?php

namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};
use Illuminate\Support\{
    Str,
    Arr,
    Collection as SupportCollection,
    Facades\DB,
};
use Closure;

class CompanyRepository extends SearchableRepository implements CompanyRepositoryInterface
{
    protected Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function data($additional = []): SupportCollection
    {
        return collect([
            'types' => Company::TYPES,
            'categories' => Company::CATEGORIES
        ])
            ->union($additional);
    }

    public function allInternal(array $columns = ['*']): Collection
    {
        return $this->company->query()
            ->whereType('Internal')
            ->activated()
            ->ordered()
            ->get($columns);
    }

    public function allWithVendorsAndCountries(): Collection
    {
        $companies = $this->company->query()->with([
            'vendors' => fn ($query) => $query->activated(),
            'vendors.countries.defaultCurrency',
        ])
            ->activated()->ordered()->get();

        $companies->map->sortVendorsCountries();

        return $companies;
    }

    public function allInternalWithVendorsAndCountries(): Collection
    {
        $companies = $this->company
            ->query()
            ->whereType('Internal')
            ->with([
                'vendors' => fn ($query) => $query->activated(),
                'vendors.countries.defaultCurrency',
            ])
            ->activated()->ordered()->get();

        $companies->map->sortVendorsCountries();

        return $companies;
    }

    public function allInternalWithCountries(array $columns = ['*']): Collection
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        $companies = $this->company->query()
            ->whereType('Internal')
            ->with([
                'countries' => fn ($query) => $query
                    ->select('countries.id', 'countries.iso_3166_2', 'countries.name', 'countries.flag')
                    ->whereNotNull('vendors.activated_at')
                    ->addSelect([
                        'default_country_id' => fn ($query) => $query->select('default_country_id')->from('companies')->whereColumn('companies.id', 'company_vendor.company_id')
                    ])
                    ->orderByRaw('FIELD(countries.id, default_country_id, ?, NULL) DESC', [optional($user)->country_id])
            ])
            ->whereNotNull('companies.activated_at')
            ->orderByRaw('FIELD(companies.id, NULL, ?) DESC', [optional($user)->company_id])
            ->get(array_merge($columns, ['default_country_id']));

        return $companies;
    }

    public function allExternal(array $where = []): Collection
    {
        return $this->userQuery()->where($where)->whereType('External')->get(['id', 'name']);
    }

    public function searchExternal(?string $query, int $limit = 15)
    {
        if (blank($query)) {
            return Collection::make();
        }

        return $this->company->query()->whereType('External')->where('name', 'like', Str::of($query)->append('%'))->get(['id', 'name']);
    }

    public function userQuery(): Builder
    {
        return $this->company->query()
            ->unless(auth()->user()->hasRole(R_SUPER), fn (Builder $q) => $q->whereUserId(auth()->id()));
    }

    public function count(array $where = []): int
    {
        return $this->company->query()->where($where)->count();
    }

    public function find(string $id): Company
    {
        return $this->company->whereKey($id)->firstOrFail();
    }

    public function findByVat(string $vat)
    {
        return $this->company->query()->whereVat($vat)->first();
    }

    public function random(int $limit = 1, ?Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->company->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof Closure) {
            $scope($query);
        }

        return $query->{$method}();
    }

    public function create(array $attributes): Company
    {
        return DB::transaction(
            fn () => tap($this->company->make($attributes), function (Company $company) use ($attributes) {
                $company->save();

                $company->createLogo(Arr::get($attributes, 'logo'));

                $company->syncVendors(Arr::get($attributes, 'vendors'));

                $company->syncAddresses(Arr::get($attributes, 'addresses_attach'));

                $company->syncContacts(Arr::get($attributes, 'contacts_attach'));
            })
        );
    }

    public function update(string $id, array $attributes): Company
    {
        return DB::transaction(
            fn () =>
            tap($this->find($id), function (Company $company) use ($attributes) {
                $company->update($attributes);

                $company->createLogo(Arr::get($attributes, 'logo'));

                if ($attributes['delete_logo'] ?? false) {
                    $company->image()->flushQueryCache()->delete();
                }

                $company->syncVendors(Arr::get($attributes, 'vendors'));

                $company->syncAddresses(Arr::get($attributes, 'addresses_attach'));

                $company->syncContacts(Arr::get($attributes, 'contacts_attach'));
            })
        );
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
        return $this->company->query()->with('image')->country($id)->activated()->get();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class,
            \App\Http\Query\Company\OrderByVat::class,
            \App\Http\Query\Company\OrderByPhone::class,
            \App\Http\Query\Company\OrderByWebsite::class,
            \App\Http\Query\Company\OrderByEmail::class,
            \App\Http\Query\Company\OrderByType::class,
            \App\Http\Query\Company\OrderByCategory::class,
            \App\Http\Query\Company\OrderByTotalQuotedValue::class,
            app(\App\Http\Query\DefaultOrderBy::class, ['column' => 'total_quoted_value']),
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->with('image')->withTotalQuotedValue()->activated(),
            $this->userQuery()->with('image')->withTotalQuotedValue()->deactivated()
        ];
    }

    protected function searchableQuery()
    {
        return $this->userQuery()->with('image')->withTotalQuotedValue();
    }

    protected function searchableModel(): Model
    {
        return $this->company;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'vat^4', 'type^3', 'email^3', 'phone^3', 'created_at^2'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('image');
    }
}
