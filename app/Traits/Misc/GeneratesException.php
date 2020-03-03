<?php

namespace App\Traits\Misc;

use Arr, Str;

trait GeneratesException
{
    public function setException(string $message, string $code): bool
    {
        return cache()->forever($this->getExceptionCacheKey(), compact('message', 'code'));
    }

    public function getExceptionAttribute()
    {
        return cache($this->getExceptionCacheKey(), false);
    }

    public function hasException(): bool
    {
        return (bool) $this->exception;
    }

    public function clearException()
    {
        return cache()->forget($this->getExceptionCacheKey());
    }

    public function throwExceptionIfExists()
    {
        if ($this->exception && Arr::has($this->exception, ['message', 'code'])) {
            error_abort($this->exception['message'], $this->exception['code'], 422);
        }
    }

    protected function getExceptionCacheKey(): string
    {
        return Str::snake(class_basename($this)) . '_exception:' . $this->getKey();
    }
}
