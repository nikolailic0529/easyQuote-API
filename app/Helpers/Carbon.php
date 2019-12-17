<?php

use Illuminate\Support\Carbon;

if (!function_exists('carbon_parse')) {
    function carbon_format($time = null, $format)
    {
        return isset($time)
            ? Carbon::parse($time)->format($format)
            : $time;
    }
}
