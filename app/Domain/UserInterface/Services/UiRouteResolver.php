<?php

namespace App\Domain\UserInterface\Services;

use App\Domain\UserInterface\Contracts\RouteResolver;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UiRouteResolver implements RouteResolver
{
    public function __construct(protected Repository $config)
    {
    }

    public function route(string $route, ?array $context = null): string
    {
        $rootPath = $this->getRootUrl();

        $route = $this->resolveRoute($route);

        if (isset($context)) {
            $route = \strtr($route, $this->resolveContext($context));
        }

        $rootPath = Str::finish($rootPath, '/');

        return $rootPath.$route;
    }

    protected function resolveContext(array $context): array
    {
        $replacement = [];

        foreach ($context as $k => $v) {
            $replacement['{'.$k.'}'] = transform(
                $v, static fn ($v) => $v instanceof Model ? $v->getRouteKey() : $v
            );
        }

        return $replacement;
    }

    protected function getRootUrl(): string
    {
        return $this->config->get('app.ui_url', '');
    }

    protected function getRegisteredRoutes(): array
    {
        return $this->config->get('ui.routes');
    }

    protected function resolveRoute(string $key): ?string
    {
        return Arr::get($this->getRegisteredRoutes(), $key);
    }
}
