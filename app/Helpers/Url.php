<?php

use Illuminate\Support\Str;

if (!function_exists('assetExternal')) {
    function assetExternal(string $path)
    {
        return app('url')->assetFrom(config('app.url_external'), $path);
    }
}

if (!function_exists('ui_route')) {
    function ui_route(string $route, ?array $parameters = null)
    {
        $rootPath = config('app.ui_url');

        $route = config('ui.routes.' . $route);

        if (isset($parameters)) {
            $route = preg_replace_callback('/\{(\w+)\}/', function ($match) use ($parameters) {
                return data_get($parameters, $match[1]);
            }, $route);
        }

        if (!Str::endsWith($rootPath, '/')) {
            $rootPath .= '/';
        }

        return $rootPath . $route;
    }
}
