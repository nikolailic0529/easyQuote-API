<?php

use Illuminate\Support\Arr;

if (!function_exists('array_flatten')) {
    function array_flatten($array, $depth = INF) {
        return Arr::flatten($array, $depth);
    }
}
