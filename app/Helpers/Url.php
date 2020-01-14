<?php

if (!function_exists('assetExternal')) {
    function assetExternal(string $path)
    {
        return app('url')->assetFrom(config('app.url_external'), $path);
    }
}

if (!function_exists('ui_route')) {
    function ui_route(string $route, ?array $context = null)
    {
        return app('ui.service')->route($route, $context);
    }
}
