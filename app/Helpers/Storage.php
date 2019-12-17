<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_exists')) {
    function storage_exists($path)
    {
        return Storage::exists($path);
    }
}

if (!function_exists('storage_missing')) {
    function storage_missing($path)
    {
        return !storage_exists($path);
    }
}

if (!function_exists('storage_mkdir')) {
    function storage_mkdir($directory)
    {
        return Storage::makeDirectory($directory);
    }
}

if (!function_exists('storage_put')) {
    function storage_put($path, $contents, $options = [])
    {
        return Storage::put($path, $contents, $options);
    }
}

if (!function_exists('storage_real_path')) {
    function storage_real_path($path)
    {
        return Storage::path($path);
    }
}
