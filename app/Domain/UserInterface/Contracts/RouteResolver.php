<?php

namespace App\Domain\UserInterface\Contracts;

interface RouteResolver
{
    /**
     * Resolve UI route path with specified context.
     */
    public function route(string $route, ?array $context = null): string;
}
