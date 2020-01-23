<?php

namespace App\Factories\Failure;

use Illuminate\Contracts\Support\Arrayable;
use ArrayAccess, Arr;
use Exception;

class FailureHelp implements ArrayAccess, Arrayable
{
    protected $attributes;

    public function __construct(Exception $exception, iterable $reasons, iterable $resolving)
    {
        $this->attributes = [
            'exception' => $exception,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'reasons' => $reasons,
            'resolving' => $resolving
        ];
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public static function create(Exception $exception, iterable $reasons, iterable $resolving)
    {
        return new static($exception, $reasons, $resolving);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return !is_null($this->attributes[$offset] ?? null);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function toArray()
    {
        return Arr::only($this->attributes, ['reasons', 'resolving']);
    }
}
