<?php

namespace App\Services;

use App\Contracts\Services\ReportLoggerInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\{
    Arr,
    Str,
    Collection,
};
use Throwable;

class ReportLogger implements ReportLoggerInterface
{
    protected static array $infoKeys = ['message'];

    protected static array $errorKeys = ['ErrorCode', 'error'];

    public function log(): void
    {
        $arguments = collect(func_get_args());

        $first = $arguments->shift();
        $response = static::formatResponse($arguments);

        $this->logCase($first, $response);
    }

    public function formatError(string $message, Throwable $e): string
    {
        return sprintf('%s [%s]', $message, $e->getMessage());
    }

    protected function logCase(array $argument, Collection $response): void
    {
        $method = static::resolveLogMethod($argument);

        $message = head($argument);

        if ($response->isNotEmpty()) {
            $message = static::fetchResponse($message, $response);
        }

        logger()->{$method}($message);

        if ($response->isNotEmpty()) {
            $response->each(fn ($value) => logger()->{$method}($value));
        }
    }

    protected static function resolveLogMethod(array $argument)
    {
        if (Arr::hasAny($argument, static::$infoKeys)) {
            return 'info';
        }

        if (Arr::hasAny($argument, static::$errorKeys)) {
            return 'error';
        }

        return 'info';
    }

    protected static function formatResponse(Collection $response): Collection
    {
        return $response->map(function ($argument) {
            if ($argument instanceof JsonResource) {
                return $argument->resolve();
            }

            return $argument;
        })
            ->filter();
    }

    protected static function fetchResponse(string $message, Collection $response): string
    {
        return (string) Str::of($message)
            ->replaceMatches('/\B\:([\w\.]+)\b/', fn ($key) => static::findInResponse($key[1], $response));
    }

    private static function findInResponse($key, Collection $response)
    {
        foreach ($response as $argument) {
            if (Arr::accessible($argument) && Arr::has($argument, $key)) {
                return Arr::get($argument, $key);
            }
        }

        return $key;
    }
}
