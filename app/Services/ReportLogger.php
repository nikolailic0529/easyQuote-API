<?php

namespace App\Services;

use App\Contracts\Services\ReportLoggerInterface;

class ReportLogger implements ReportLoggerInterface
{
    static protected $infoKeys = ['message'];

    static protected $errorKeys = ['ErrorCode', 'error'];

    public function log(): void
    {
        $arguments = collect(func_get_args());

        $response = $arguments->mapWithKeys(function ($value, $key) {
            return [$key => $value];
        });

        $response->shift();

        $this->logCase($arguments->first(), $response->toArray());
    }

    protected function logCase(array $argument, array $response): void
    {
        $key = $this->findLogKey($argument);
        $method = isset(array_flip(static::$infoKeys)[$key]) ? 'info' : 'error';

        $message = preg_replace_callback('/\B\:([\w\.]+)\b/', function ($key) use ($response) {
            return data_get(head($response), $key[1]);
        }, $argument[$key]);

        $response = json_encode($response, JSON_PRETTY_PRINT);

        logger()->{$method}(['message' => $message, 'response' => $response]);
    }

    protected function findLogKey(array $argument)
    {
        $logKeys = array_flip(array_merge(static::$infoKeys, static::$errorKeys));

        return collect($argument)->search(function ($value, $key) use ($logKeys) {
            return isset($logKeys[$key]);
        });
    }
}
