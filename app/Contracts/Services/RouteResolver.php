<?php

namespace App\Contracts\Services;

interface RouteResolver
{
    /**
     * Resolve UI route path with specified context.
     *
     * @param string $route
     * @param array|null $context
     * @return string
     */
    public function route(string $route, ?array $context = null): string;
}
