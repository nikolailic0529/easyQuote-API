<?php

namespace App\Contracts\Services;

interface UIServiceInterface
{
    /**
     * Resolve UI route path with specified context.
     *
     * @param string $route
     * @param array|null $context
     * @return string
     */
    public static function route(string $route, ?array $context = null): string;
}
