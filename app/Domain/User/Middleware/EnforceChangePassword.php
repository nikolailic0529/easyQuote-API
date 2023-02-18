<?php

namespace App\Domain\User\Middleware;

use App\Domain\User\Models\User;
use Illuminate\Http\Request;

class EnforceChangePassword
{
    public function __construct(
        protected readonly array $config,
    ) {
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        if ($this->isPasswordExpirationDisabled()) {
            return $next($request);
        }

        /** @var User $user */
        $user = $request->user();

        if (null === $user) {
            return $next($request);
        }

        if ($this->isIgnoredRoute($request)) {
            return $next($request);
        }

        error_abort_if($user->mustChangePassword(), MCP_00, 'MCP_00', 422);

        return $next($request);
    }

    protected function isIgnoredRoute(Request $request): bool
    {
        return collect($this->getIgnoredRoutes())->containsStrict(
            $request->route()?->getName()
        );
    }

    protected function getIgnoredRoutes(): array
    {
        return $this->config['ignored_routes'] ?? [];
    }

    protected function isPasswordExpirationEnabled(): bool
    {
        return filter_var($this->config['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function isPasswordExpirationDisabled(): bool
    {
        return !$this->isPasswordExpirationEnabled();
    }
}
