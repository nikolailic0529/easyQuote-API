<?php

namespace App\Contracts\Repositories\System;

use App\Models\System\Build;

interface BuildRepositoryInterface
{
    /**
     * Retrieve all the Builds.
     *
     * @return mixed
     */
    public function all();

    /**
     * Find the specified Build by the given id.
     *
     * @param string $id
     * @return \App\Models\System\Build|null
     */
    public function find(string $id);

    /**
     * Create a new Build with specified attributes.
     *
     * @param array $attributes
     * @return \App\Models\System\Build
     */
    public function create(array $attributes): Build;

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\System\Build|static
     */
    public function firstOrCreate(array $attributes, array $values = []);

    /**
     * Update last build or create new one.
     *
     * @param array $attributes
     * @return \App\Models\System\Build
     */
    public function updateLastOrCreate(array $attributes): Build;

    /**
     * Retrieve last Build from storage.
     *
     * @return \App\Models\System|null
     */
    public function last();
}
