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

if (!function_exists('ui_path')) {
    function ui_path(string $path = '')
    {
        return config('ui.path').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
