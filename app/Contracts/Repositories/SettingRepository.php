<?php

namespace App\Contracts\Repositories;

use ArrayAccess;

interface SettingRepository extends ArrayAccess
{
    /**
     * Get the specified setting value.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set a given setting value.
     *
     * @param string $key
     * @param $value
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * Determine if the given setting value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
}