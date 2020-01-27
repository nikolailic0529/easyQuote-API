<?php

namespace App\Repositories;

use App\Contracts\Repositories\ExchangeRateRepositoryInterface;
use App\Models\Data\ExchangeRate;

class ExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    const CACHE_PREFIX = 'exchange_rate:';

    /** @var \App\Models\Data\ExchangeRate */
    protected $exchangeRate;

    public function __construct(ExchangeRate $exchangeRate)
    {
        $this->exchangeRate = $exchangeRate;
    }

    public function find(string $id)
    {
        return $this->exchangeRate->whereId($id)->first();
    }

    public function create(array $attributes): ExchangeRate
    {
        return $this->exchangeRate->create($attributes);
    }

    public function firstOrCreate(array $attributes, array $values = []): ExchangeRate
    {
        return $this->exchangeRate->firstOrCreate($attributes, $values);
    }
}
