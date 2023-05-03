<?php

namespace App\Domain\Currency\Contracts;

use App\Domain\Currency\Models\Currency;

interface CurrencyRepositoryInterface
{
    /**
     * Disable using cache in the repository.
     */
    public function disableCache(): void;

    /**
     * Enable using cache in the repository.
     */
    public function enableCache(): void;

    /**
     * Get all currencies.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Retrieve all currencies which have exchange rate or are base currency.
     *
     * @return mixed
     */
    public function allHaveExrate();

    /**
     * Retrieve the specified currency.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Currency;

    /**
     * Retrieve the specified currency by id.
     */
    public function find(string $id): ?Currency;

    /**
     * Retrieve the specified currency by id from the cache repository.
     */
    public function findCached(string $id): ?Currency;

    /**
     * Retrieve Currency passed Currency code.
     *
     * @return \App\Domain\Currency\Models\Currency|null
     */
    public function findByCode(?string $code);

    /**
     * Retrieve Currency Id by passed Currency code.
     *
     * @param string|array $code
     *
     * @return string|null
     */
    public function findIdByCode($code);

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @return \App\Domain\Currency\Models\Currency|static
     */
    public function firstOrCreate(array $attributes, array $values = []): Currency;

    /**
     * Retrieve the default currency for the specified country.
     */
    public function findByCountryCode(string $country): ?Currency;
}
