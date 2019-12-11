<?php

use Illuminate\Support\Arr;

if (!function_exists('array_flatten')) {
    function array_flatten($array, $depth = INF) {
        return Arr::flatten($array, $depth);
    }
}

if (!function_exists('to_array_recursive')) {
    function to_array_recursive(iterable $iterable) {
        return json_decode(json_encode($iterable), true);
    }
}
