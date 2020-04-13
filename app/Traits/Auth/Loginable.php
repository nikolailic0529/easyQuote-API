<?php

namespace App\Traits\Auth;

use Illuminate\Database\Eloquent\Builder;

trait Loginable
{
    protected function initializeLoginable()
    {
        $this->casts = array_merge($this->casts, ['already_logged_in' => 'boolean']);
    }

    public function markAsLoggedIn(?string $ip = null)
    {
        $attributes = ['already_logged_in' => true];

        if (isset($ip)) {
            $attributes['ip_address'] = $ip;
        }

        return $this->withoutEvents(function () use ($attributes) {
            $usesTimestamps = $this->usesTimestamps();
            $this->timestamps = false;

            return tap($this->forceFill($attributes)->saveOrFail(), fn () => $this->timestamps = $usesTimestamps);
        });
    }

    public function markAsLoggedOut(): bool
    {
        return $this->withoutEvents(function () {
            $usesTimestamps = $this->usesTimestamps();
            $this->timestamps = false;

            return tap($this->forceFill(['already_logged_in' => false])->saveOrFail(), fn () => $this->timestamps = $usesTimestamps);
        });
    }

    public function isAlreadyLoggedIn(): bool
    {
        return (bool) $this->already_logged_in;
    }

    public function isNotAlreadyLoggedIn(): bool
    {
        return !$this->isAlreadyLoggedIn();
    }

    public function ipMatches(string $ip): bool
    {
        return $this->ip_address === $ip;
    }

    public function ipDoesntMatch(string $ip): bool
    {
        return !$this->ipMatches($ip);
    }

    public function scopeLoggedIn(Builder $query): Builder
    {
        return $query->where('already_logged_in', true);
    }

    public function scopeIp(Builder $query, string $ip): Builder
    {
        return $query->whereIpAddress($ip);
    }
}
