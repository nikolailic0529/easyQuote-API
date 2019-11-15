<?php

namespace App\Traits;

trait Expirable
{
    public function initializeExpirable()
    {
        $this->fillable = array_merge($this->fillable, ['expires_at']);
        $this->appends = array_merge($this->appends, ['is_expired']);
        $this->dates = array_merge($this->dates, ['expires_at']);
    }

    public function scopeExpired($query)
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '<', now()->toDateTimeString())
            ->limit(999999999);
    }

    public function scopeNonExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now()->toDateTimeString())
            ->limit(999999999);
    }

    public function getIsExpiredAttribute()
    {
        return is_null($this->expires_at) || $this->expires_at->lt(now());
    }
}
