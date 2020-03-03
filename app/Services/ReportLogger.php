<?php

namespace App\Services;

use App\Contracts\Services\ReportLoggerInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportLogger implements ReportLoggerInterface
{
    protected static array $infoKeys = ['message'];

    protected static array $errorKeys = ['ErrorCode', 'error'];

    public function log(): void
    {
        $arguments = collect(func_get_args());

        $first = $arguments->shift();
        $second = $arguments->shift();

        if ($second instanceof JsonResource) {
            $second = $second->resolve();
        }

        $this->logCase($first, $second);
    }

    protected function logCase(array $argument, ?array $response = null): void
    {
        $key = $this->findLogKey($argument);

        if ($key === false) {
            return;
        }

        $method = isset(array_flip(static::$infoKeys)[$key]) ? 'info' : 'error';
        $message = $argument[$key];

        if (isset($response)) {
            $message = preg_replace_callback(
                '/\B\:([\w\.]+)\b/',
                fn ($key) => data_get($response, $key[1], $key[1]),
                $message
            );
        }

        logger()->{$method}($message);

        if (isset($response)) {
            logger()->{$method}($response);
        }
    }

    protected function findLogKey(array $argument)
    {
        $logKeys = array_flip(array_merge(static::$infoKeys, static::$errorKeys));

        return collect($argument)->search(fn ($value, $key) => isset($logKeys[$key]));
    }
}
