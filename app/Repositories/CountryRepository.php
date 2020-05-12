<?php

namespace App\Repositories;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Models\Data\Country;
use Illuminate\Database\Eloquent\{
    Builder,
    Collection,
    Model
};
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;
use Closure;

class CountryRepository extends SearchableRepository implements CountryRepositoryInterface
{
    public const CACHE_TAG = 'countries';

    protected const COUNTRIES_CACHE_KEY = 'all-countries';

    protected const COUNTRY_ID_CACHE_KEY = 'country-id-iso';

    protected const COUNTRY_CACHE_KEY = 'country';

    protected Country $country;

    protected Repository $cache;

    public function __construct(Country $country, Repository $cache)
    {
        $this->country = $country;
        $this->cache = $cache;
    }

    public function query(): Builder
    {
        return $this->country->query();
    }

    public function all(): Collection
    {
        return $this->country->ordered()->get();
    }

    public function allCached(): Collection
    {
        return $this->cache->sear(
            static::COUNTRIES_CACHE_KEY,
            fn () => $this->all()
        );
    }

    public function paginate()
    {
        return parent::all();
    }

    public function find(string $id): Country
    {
        return $this->query()->whereId($id)->firstOrFail();
    }

    public function findCached(string $id): ?Country
    {
        if (Str::isUuid($id)) {
            return $this->allCached()->find($id);
        }

        $id = Str::upper($id);

        return $this->allCached()->firstWhere('iso_3166_2', $id);
    }

    public function findByCode(?string $code)
    {
        return $this->query()->where('iso_3166_2', $code)->first();
    }

    public function findIdByCode($code)
    {
        if (is_iterable($code)) {
            return $this->allCached()->whereIn('iso_3166_2', $code)->pluck('id', 'iso_3166_2');
        }

        if (is_string($code)) {
            $code = Str::upper($code);

            return optional($this->allCached()->firstWhere('iso_3166_2', $code))->getKey();
        }
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

    protected static function countryIdCacheKey(string $iso): string
    {
        return sprintf('%s.%s', static::COUNTRY_ID_CACHE_KEY, $iso);
    }

    protected static function countryCacheKey(string $id): string
    {
        return sprintf('%s.%s', static::COUNTRY_CACHE_KEY, $id);
    }
}
