<?php

namespace App\Contracts\Repositories;

use App\Models\Data\Timezone;

interface TimezoneRepositoryInterface
{
    /**
     * Get all timezones
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Retrieve a random timezone.
     *
     * @return \App\Models\Data\Timezone
     */
    public function random(): Timezone;
}
