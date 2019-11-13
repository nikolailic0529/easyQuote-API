<?php

namespace App\Contracts\Repositories\System;

interface SystemSettingRepositoryInterface
{
    /**
     * Get System Setting value by unique key
     *
     * @param string $key
     * @return string
     */
    public function get(string $key);
}
