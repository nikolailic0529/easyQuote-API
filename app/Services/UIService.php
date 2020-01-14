<?php

namespace App\Services;

use App\Contracts\Services\UIServiceInterface;
use Arr, Str;

class UIService implements UIServiceInterface
{
    /** @var string */
    protected static $rootPath;

    /** @var array */
    protected static $config;

    public function __construct()
    {
        static::$rootPath = config('app.ui_url');
        static::$config = config('ui') ?: [];
    }

    public static function route(string $route, ?array $context = null): string
    {
        $rootPath = static::$rootPath;

        $route = static::resolveRoute($route);

        if (isset($context)) {
            $route = strtr($route, static::resolveContext($context));
        }

        if (!Str::endsWith($rootPath, '/')) {
            $rootPath .= '/';
        }

        return $rootPath . $route;
    }

    protected static function resolveContext(array $context): array
    {
        $replacement = [];

        foreach ($context as $k => $v) {
            $replacement['{'.$k.'}'] = transform($v, function ($v) {
                return $v instanceof \Illuminate\Database\Eloquent\Model ? $v->getRouteKey() : $v;
            });
        }

        return $replacement;
    }

    protected static function resolveRoute(string $key)
    {
        return Arr::get(static::$config, 'routes.'.$key);
    }
}
