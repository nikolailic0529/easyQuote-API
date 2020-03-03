<?php

namespace App\Repositories;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Models\Data\Country;
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};
use Closure;

class CountryRepository extends SearchableRepository implements CountryRepositoryInterface
{
    /** @var \App\Models\Data\Country */
    protected Country $country;

    public function __construct(Country $country)
    {
        $this->country = $country;
    }

    public function query(): Builder
    {
        return $this->country->query();
    }

    public function all()
    {
        return cache()->sear('all-countries', fn () => $this->country->ordered()->get(['id', 'name']));
    }

    public function paginate()
    {
        return parent::all();
    }

    public function find(string $id): Country
    {
        return $this->query()->whereId($id)->firstOrFail();
    }

    public function findByCode(?string $code)
    {
        return $this->query()->where('iso_3166_2', $code)->first();
    }

    public function findIdByCode($code)
    {
        $iso = implode(',', (array) $code);

        if (is_array($code)) {
            return cache()->sear(
                static::getCountryIdCacheKey($iso),
                fn () => $this->country->whereIn('iso_3166_2', $code)->pluck('id', 'iso_3166_2')
            );
        }

        throw_unless(is_string($code), new \InvalidArgumentException(
            sprintf('%s %s given.', INV_ARG_SA_01, gettype($code))
        ));

        return cache()->sear(
            static::getCountryIdCacheKey($iso),
            fn () => $this->country->where('iso_3166_2', $code)->value('id')
        );
    }

    public function random(int $limit = 1, ?Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->country->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof Closure) {
            $scope($query);
        }

        return $query->{$method}();
    }

    public function create(array $attributes): Country
    {
        return $this->country->create($attributes);
    }

    public function update(array $attributes, string $id): Country
    {
        return tap($this->find($id))->update($attributes);
    }

    public function updateOrCreate(array $attributes, array $values = []): Country
    {
        return $this->country->updateOrCreate($attributes, $values);
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
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByName::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->query()->with('defaultCurrency')->activated(),
            $this->query()->with('defaultCurrency')->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->country;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'iso_3166_2^4', 'currency_code^3', 'currency_name^3', 'currency_symbol^3', 'created_at^2'
        ];
    }

    protected static function getCountryIdCacheKey(string $iso): string
    {
        return 'country-id-iso:' . $iso;
    }
}
