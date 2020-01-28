<?php

namespace App\Repositories;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Models\Data\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CountryRepository extends SearchableRepository implements CountryRepositoryInterface
{
    /** @var \App\Models\Data\Country */
    protected $country;

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
        return cache()->sear('all-countries', function () {
            return $this->country->ordered()->get(['id', 'name']);
        });
    }

    public function paginate()
    {
        return parent::all();
    }

    public function find(string $id): Country
    {
        return $this->query()->whereId($id)->firstOrFail();
    }

    public function findIdByCode($code)
    {
        if (is_array($code)) {
            $iso = implode(',', $code);
            return cache()->sear("country-id-iso:{$iso}", function () use ($code) {
                return $this->country->whereIn('iso_3166_2', $code)->pluck('id', 'iso_3166_2');
            });
        }

        throw_unless(is_string($code), new \InvalidArgumentException(
            sprintf('%s %s given.', INV_ARG_SA_01, gettype($code))
        ));

        return cache()->sear("country-id-iso:{$code}", function () use ($code) {
            return $this->country->where('iso_3166_2', $code)->value('id');
        });
    }

    public function create(array $attributes): Country
    {
        return $this->country->create($attributes);
    }

    public function update(array $attributes, string $id): Country
    {
        return tap($this->find($id))->update($attributes);
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
            $this->country->activated(),
            $this->country->deactivated()
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
}
