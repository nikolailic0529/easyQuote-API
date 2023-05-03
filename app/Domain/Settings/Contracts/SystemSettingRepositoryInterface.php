<?php

namespace App\Domain\Settings\Contracts;

use App\Domain\Settings\Models\SystemSetting;
use Illuminate\Database\Eloquent\Collection as IlluminateCollection;

interface SystemSettingRepositoryInterface
{
    /**
     * Find System Setting by id.
     */
    public function find(string $id): SystemSetting;

    /**
     * Find Many System Settings.
     */
    public function findMany(array $ids): IlluminateCollection;

    /**
     * Find a specified System Setting by key.
     */
    public function findByKey(string $key): SystemSetting;

    /**
     * Get System Setting value by provided key.
     *
     * @return string
     */
    public function get(string $key, bool $mutate = true);

    /**
     * Update a value for specified System Setting.
     *
     * @param \Illumintate\Http\Request|array $attributes
     */
    public function update($attributes, string $id): bool;

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @return \App\Domain\Settings\Models\SystemSetting|static
     */
    public function firstOrCreate(array $attributes, array $values = []);

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @return \App\Domain\Settings\Models\SystemSetting|static
     */
    public function updateOrCreate(array $attributes, array $values = []);

    /**
     * Update values for multiple System Settings.
     *
     * @param \Illumintate\Http\Request|array $attributes
     */
    public function updateMany($attributes): bool;

    /**
     * Update the specified setting by given key.
     */
    public function updateByKeys(array $map): bool;

    /**
     * Retrieve all existing System Settings with current and possible values.
     */
    public function all(): IlluminateCollection;
}
