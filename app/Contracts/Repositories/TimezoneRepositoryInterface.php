<?php namespace App\Contracts\Repositories;

interface TimezoneRepositoryInterface
{
    /**
     * Get all timezones
     *
     * @return \Illuminate\Database\Eloquent\Collection 
     */
    public function all();
}
