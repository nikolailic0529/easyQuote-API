<?php

use App\Contracts\Services\HttpInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

if (!function_exists('to_array_recursive')) {
    function to_array_recursive(iterable $iterable)
    {
        return json_decode(json_encode($iterable), true);
    }
}

if (!function_exists('carbon_parse')) {
    function carbon_format($time, $format)
    {
        return transform($time, fn ($time) => Carbon::parse($time)->format($format));
    }
}

if (!function_exists('slack')) {
    /**
     * Slack notification client.
     *
     * @return App\Contracts\Services\SlackInterface
     */
    function slack()
    {
        if (func_num_args() > 0) {
            return app('slack.client')->send(...func_get_args());
        }

        return app('slack.client');
    }
}

if (!function_exists('notification')) {
    /**
     * Begin Pending Notification instance.
     *
     * @param array $attributes
     * @return \App\Contracts\Services\NotificationInterface
     */
    function notification(array $attributes = [])
    {
        return app('notification.dispatcher')->setAttributes($attributes);
    }
}

/**
 * Filter Query String Parameters for the given resource.
 */
if (!function_exists('filter')) {
    function filter($resource)
    {
        return app('request.filter')->attach($resource);
    }
}

if (!function_exists('error_response')) {
    function error_response(string $details, string $code, int $status, array $headers = [])
    {
        /** @var HttpInterface */
        $http = app(HttpInterface::class);

        return $http->makeErrorResponse($details, $code, $status, $headers);
    }
}

if (!function_exists('error_abort')) {
    function error_abort(string $details, string $code, int $status, array $headers = [])
    {
        abort(error_response($details, $code, $status, $headers));
    }
}

if (!function_exists('error_abort_if')) {
    function error_abort_if($boolean, $details, $code, $status, $headers = [])
    {
        if ($boolean) {
            error_abort($details, $code, $status, $headers);
        }
    }
}

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
