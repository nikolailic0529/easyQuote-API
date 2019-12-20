<?php

namespace App\Services;

use App\Contracts\Services\ReportLoggerInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportLogger implements ReportLoggerInterface
{
    static protected $infoKeys = ['message'];

    static protected $errorKeys = ['ErrorCode', 'error'];

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

    protected function logCase(array $argument, array $response): void
    {
        $key = $this->findLogKey($argument);

        if ($key === false) {
            return;
        }

        $method = isset(array_flip(static::$infoKeys)[$key]) ? 'info' : 'error';

        $message = preg_replace_callback('/\B\:([\w\.]+)\b/', function ($key) use ($response) {
            return data_get($response, $key[1]) ?? $key[1];
        }, $argument[$key]);

        logger()->{$method}($message);
        logger()->{$method}($response);
    }

    protected function findLogKey(array $argument)
    {
        $logKeys = array_flip(array_merge(static::$infoKeys, static::$errorKeys));

        return collect($argument)->search(function ($value, $key) use ($logKeys) {
            return isset($logKeys[$key]);
        });
    }
}
