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
     * Retrieve Country Id by passed Country ISO code.
     *
     * @param string|array $code
     * @return string|null
     */
    public function findIdByCode($code);
}
