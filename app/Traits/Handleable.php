<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait Handleable
{
    public function markAsHandled(): bool
    {
        return $this->forceFill([
            'handled_at' => Carbon::now()->toDateTimeString(),
        ])->save();
    }

    public function markAsUnHandled(): bool
    {
        return $this->forceFill([
            'handled_at' => null,
        ])->save();
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
}
