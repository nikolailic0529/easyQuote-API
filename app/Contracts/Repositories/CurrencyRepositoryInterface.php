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
}
