<?php

if (!function_exists('assetExternal')) {
    function assetExternal(string $path)
    {
        return app('url')->assetFrom(config('app.url_external'), $path);
    }
}
