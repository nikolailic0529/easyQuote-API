<?php

use App\Facades\Setting;

if (!function_exists('setting')) {
    function setting(?string $key = null) {
        if (isset($key)) {
            return Setting::get($key);
        }

        return app('setting.repository');
    }
}
