<?php

if (!function_exists('setting')) {
    function setting(?string $key = null)
    {
        if (isset($key)) {
            return app('setting.repository')->get($key);
        }

        return app('setting.repository');
    }
}
