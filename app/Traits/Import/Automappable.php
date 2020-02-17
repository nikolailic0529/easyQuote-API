<?php

namespace App\Traits\Import;

trait Automappable
{
    public function markAsAutomapped(): bool
    {
        return $this->forceFill(['automapped_at' => now()])->save();
    }

    public function markAsNotAutomapped(): bool
    {
        return $this->forceFill(['automapped_at' => null])->save();
    }

    public function isNotAutomapped(): bool
    {
        return !isset($this->automapped_at);
    }
}
