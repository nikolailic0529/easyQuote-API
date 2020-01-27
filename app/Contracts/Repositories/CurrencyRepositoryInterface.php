<?php

namespace App\Contracts\Repositories;

interface CurrencyRepositoryInterface
{
    /**
     * Get all currencies
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Retrieve Currency Id by passed Currency code.
     *
     * @param string|array $code
     * @return string|null
     */
    public function findIdByCode($code);
}
