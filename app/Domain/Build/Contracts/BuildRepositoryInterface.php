<?php

namespace App\Domain\Build\Contracts;

use App\Domain\Build\Models\Build;

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
     * @return \App\Domain\Build\Models\Build|null
     */
    public function find(string $id);

    /**
     * Create a new Build with specified attributes.
     */
    public function create(array $attributes): Build;

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @return \App\Domain\Build\Models\Build|static
     */
    public function firstOrCreate(array $attributes, array $values = []);

    /**
     * Update last build or create new one.
     */
    public function updateLastOrCreate(array $attributes): Build;

    /**
     * Retrieve last Build from storage.
     *
     * @return \App\Models\System|null
     */
    public function last();
}
