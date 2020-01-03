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
     * Update values for multiple System Settings.
     *
     * @param \Illumintate\Http\Request|array $attributes
     * @return bool
     */
    public function updateMany($attributes): bool;

    /**
     * Retrieve all existing System Settings with current and possible values.
     *
     * @return IlluminateCollection
     */
    public function all(): IlluminateCollection;
}
