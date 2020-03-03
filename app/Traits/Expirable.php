<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Expirable
{
    public function initializeExpirable()
    {
        $this->fillable = array_merge($this->fillable, ['expires_at']);
        $this->appends = array_merge($this->appends, ['is_expired']);
        $this->dates = array_merge($this->dates, ['expires_at']);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '<', now())
            ->limit(999999999);
    }

    public function scopeNonExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->limit(999999999);
    }

    public function getIsExpiredAttribute(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->lt(now());
    }
}
