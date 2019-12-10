<?php

namespace App\Traits\Auth;

trait Loginable
{
    protected function initializeLoginable()
    {
        $this->casts = array_merge($this->casts, ['already_logged_in' => 'boolean']);
    }

    public function markAsLoggedIn()
    {
        return $this->forceFill(['already_logged_in' => true])->save();
    }

    public function markAsLoggedOut()
    {
        return $this->forceFill(['already_logged_in' => false])->save();
    }

    public function isAlreadyLoggedIn()
    {
        return (bool) $this->already_logged_in;
    }
}
