<?php

use App\Domain\Notification\Contracts\NotificationFactory;
use App\Foundation\Http\Contracts\HttpInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

if (!function_exists('to_array_recursive')) {
    function to_array_recursive(iterable $iterable)
    {
        return json_decode(json_encode($iterable), true);
    }
}

if (!function_exists('carbon_format')) {
    function carbon_format($time, $format)
    {
        return transform($time, fn ($time) => Carbon::parse($time)->format($format));
    }
}

if (!function_exists('slack')) {
    /**
     * Slack notification client.
     *
     * @return \App\Domain\Slack\Contracts\SlackInterface
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
     */
    function notification(): NotificationFactory
    {
        return app('notification.factory');
    }
}

/*
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
        /** @var \App\Foundation\Http\Contracts\HttpInterface */
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
    function ui_route(string $route, ?array $context = null): string
    {
        return app('ui.route')->route($route, $context);
    }
}

if (!function_exists('format')) {
    /**
     * Format the given value using the standard formatter.
     */
    function format(string $formatter, mixed $value, mixed ...$parameters): mixed
    {
        return app('formatter')->format($formatter, $value, ...$parameters);
    }
}

if (!function_exists('format')) {
    /**
     * Format the given value using the standard formatter.
     */
    function format(string $formatter, mixed $value, mixed ...$parameters): mixed
    {
        return app('formatter')->format($formatter, $value, ...$parameters);
    }
}

if (!function_exists('coalesce_blank')) {
    /**
     * Return the first not-blank argument.
     */
    function coalesce_blank(mixed ...$args): mixed
    {
        if (!count($args)) {
            return null;
        }

        $value = array_shift($args);

        if (blank($value)) {
            return coalesce_blank(...$args);
        }

        return $value;
    }
}

if (!function_exists('blank_html')) {
    /**
     * Determine if the given value is "blank" html.
     */
    function blank_html(mixed $value): bool
    {
        if (is_string($value)) {
            $value = strip_tags($value);
        }

        return blank($value);
    }
}
