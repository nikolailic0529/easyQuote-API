<?php

namespace App\Traits\Misc;

use Arr;

trait GeneratesException
{
    public function setException(string $message, string $code): bool
    {
        return cache()->forever("quote_file_exception:{$this->id}", compact('message', 'code'));
    }

    public function getExceptionAttribute()
    {
        return cache("quote_file_exception:{$this->id}", false);
    }

    public function hasException(): bool
    {
        return (bool) $this->exception;
    }

    public function clearException()
    {
        return cache()->forget("quote_file_exception:{$this->id}");
    }

    public function throwExceptionIfExists()
    {
        if ($this->exception && Arr::has($this->exception, ['message', 'code'])) {
            error_abort($this->exception['message'], $this->exception['code'], 422);
        }
    }
}
