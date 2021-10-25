<?php

namespace App\Traits\User;

trait PerformsActivity
{
    public function initializePerformsActivity()
    {
        $this->dates = array_merge($this->dates, ['logged_in_at']);
    }
}
