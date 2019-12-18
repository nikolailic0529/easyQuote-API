<?php

namespace App\Traits\Auth;

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

        return $this->forceFill($attributes)->save();
    }

    public function markAsLoggedOut()
    {
        return $this->forceFill(['already_logged_in' => false])->save();
    }

    public function isAlreadyLoggedIn()
    {
        return (bool) $this->already_logged_in;
    }

    public function ipMatches(string $ip)
    {
        return $this->ip_address === $ip;
    }

    public function ipDoesntMatch(string $ip)
    {
        return !$this->ipMatches($ip);
    }
}
