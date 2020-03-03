<?php

namespace App\Traits\Import;

use Illuminate\Database\Eloquent\Builder;

trait Proccessable
{
    public function markAsProcessed(): bool
    {
        return $this->forceFill(['processed_at' => now()])->save();
    }

    public function markAsNotProcessed(): bool
    {
        return $this->forceFill(['processed_at' => null])->save();
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeNotProcessed(Builder $query): Builder
    {
        return $query->whereNull('processed_at');
    }
}
