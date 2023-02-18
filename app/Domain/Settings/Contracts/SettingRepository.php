<?php

namespace App\Domain\Settings\Contracts;

interface SettingRepository extends \ArrayAccess
{
    /**
     * Get the specified setting value.
     *
     * @param null $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set a given setting value.
     *
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * Determine if the given setting value exists.
     */
    public function has(string $key): bool;
}
