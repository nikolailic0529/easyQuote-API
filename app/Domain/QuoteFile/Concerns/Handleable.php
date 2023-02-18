<?php

namespace App\Domain\QuoteFile\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Handleable
{
    protected $shouldBeHandled = false;

    public function markAsHandled(): bool
    {
        return $this->forceFill(['handled_at' => now()])->save();
    }

    public function markAsUnHandled(): bool
    {
        return $this->forceFill(['handled_at' => null])->save();
    }

    public function isHandled(): bool
    {
        return !is_null($this->handled_at);
    }

    public function scopeHandled(Builder $query): Builder
    {
        return $query->whereNotNull('handled_at');
    }

    public function scopeUnHandled(Builder $query): Builder
    {
        return $query->whereNull('handled_at');
    }

    public function shouldHandle(): self
    {
        $this->shouldBeHandled = true;

        return $this;
    }

    public function shouldNotHandle(): self
    {
        $this->shouldBeHandled = false;

        return $this;
    }

    public function getShouldBeHandledAttribute(): bool
    {
        return $this->shouldBeHandled;
    }

    public function getShouldNotBeHandledAttribute(): bool
    {
        return !$this->shouldBeHandled;
    }
}
