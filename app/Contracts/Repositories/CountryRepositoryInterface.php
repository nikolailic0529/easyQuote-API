<?php

namespace App\Contracts\Repositories;

interface CountryRepositoryInterface
{
    /**
     * Get all timezones
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();
}
