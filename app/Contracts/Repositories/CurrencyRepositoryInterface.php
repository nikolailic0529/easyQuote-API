<?php

namespace App\Contracts\Repositories;

use App\Models\Data\Currency;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface CurrencyRepositoryInterface
{
    /**
     * Get all currencies
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Retrieve all currencies which have exchange rate or are base currency.
     *
     * @return void
     */
    public function allHaveExrate();

    /**
     * Retrieve the specified currency.
     *
     * @param string $id
     * @return \App\Models\Data\Currency
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): Currency;

    /**
     * Retrieve Currency passed Currency code.
     *
     * @param string|null $code
     * @return string|null
     */
    public function findByCode(?string $code);

    /**
     * Retrieve Currency Id by passed Currency code.
     *
     * @param string|array $code
     * @return string|null
     */
    public function findIdByCode($code);

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\Data\Currency|static
     */
    public function firstOrCreate(array $attributes, array $values = []): Currency;
}
