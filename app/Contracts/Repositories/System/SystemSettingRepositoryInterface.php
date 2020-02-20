<?php

namespace App\Contracts\Repositories\System;

use App\Models\System\SystemSetting;
use Illuminate\Database\Eloquent\Collection as IlluminateCollection;
use Illuminate\Support\Collection;

interface SystemSettingRepositoryInterface
{
    /**
     * Find System Setting by id.
     *
     * @param string $id
     * @return SystemSetting
     */
    public function find(string $id): SystemSetting;

    /**
     * Find Many System Settings.
     *
     * @param array $ids
     * @return IlluminateCollection
     */
    public function findMany(array $ids): IlluminateCollection;

    /**
     * Find a specified System Setting by key.
     *
     * @param string $key
     * @return SystemSetting
     */
    public function findByKey(string $key): SystemSetting;

    /**
     * Get System Setting value by provided key.
     *
     * @param string $key
     * @param bool $mutate
     * @return string
     */
    public function get(string $key, bool $mutate = true);

    /**
     * Update a value for specified System Setting.
     *
     * @param \Illumintate\Http\Request|array $attributes
     * @param string $id
     * @return bool
     */
    public function update($attributes, string $id): bool;

    /**
     * Create or retrieve a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\System\SystemSetting|static
     */
    public function firstOrCreate(array $attributes, array $values = []);

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \App\Models\System\SystemSetting|static
     */
    public function updateOrCreate(array $attributes, array $values = []);

    /**
     * Update values for multiple System Settings.
     *
     * @param \Illumintate\Http\Request|array $attributes
     * @return bool
     */
    public function updateMany($attributes): bool;

    /**
     * Update the specified setting by given key.
     *
     * @param array $map
     * @return boolean
     */
    public function updateByKeys(array $map): bool;

    /**
     * Retrieve all existing System Settings with current and possible values.
     *
     * @return IlluminateCollection
     */
    public function all(): IlluminateCollection;
}
