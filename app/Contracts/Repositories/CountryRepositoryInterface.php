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

    /**
     * Retrieve Country Id by passed ISO 3166 2 code.
     *
     * @param string|array $code
     * @return string|null
     */
    public function findIdByCode($code);
}
