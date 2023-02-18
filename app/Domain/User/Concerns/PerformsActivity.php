<?php

namespace App\Domain\User\Concerns;

trait PerformsActivity
{
    public function initializePerformsActivity()
    {
        $this->dates = array_merge($this->dates, ['logged_in_at']);
    }
}
