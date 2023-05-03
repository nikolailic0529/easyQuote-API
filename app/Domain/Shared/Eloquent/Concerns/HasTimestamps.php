<?php

namespace App\Domain\Shared\Eloquent\Concerns;

/**
 * @mixin \Illuminate\Database\Eloquent\Concerns\HasTimestamps
 */
trait HasTimestamps
{
    public function withoutTimestamps(callable $callback): mixed
    {
        $usesTimestamps = $this->usesTimestamps();
        $this->timestamps = false;

        try {
            return $callback($this);
        } finally {
            $this->timestamps = $usesTimestamps;
        }
    }
}
