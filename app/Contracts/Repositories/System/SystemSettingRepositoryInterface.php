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
     * Get System Setting value by provided key.
     *
     * @param string $key
     * @return string
     */
    public function get(string $key);

    /**
     * Set System Setting value by provided key.
     *
     * @param \Illumintate\Http\Request|array $attributes
     * @param string $id
     * @return bool
     */
    public function update($attributes, string $id): bool;

    /**
     * Retrieve all existing System Settings with current and possible values.
     *
     * @return IlluminateCollection
     */
    public function all(): IlluminateCollection;
}
