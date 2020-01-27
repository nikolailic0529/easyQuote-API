<?php

namespace App\Contracts\Repositories;

use App\Models\Data\ExchangeRate;

interface ExchangeRateRepositoryInterface
{
    /**
     * Create a new Exchange Rate with provided attributes.
     *
     * @param array $attributes
     * @return \App\Models\Data\ExchangeRate
     */
    public function create(array $attributes): ExchangeRate;

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\System\Build|static
     */
    public function firstOrCreate(array $attributes, array $values = []): ExchangeRate;

    /**
     * Retrieve Exchange Rate by the specified id.
     *
     * @param string $id
     * @return \App\Models\System\Build|null
     */
    public function find(string $id);
}
