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

    public function isMapped(): bool
    {
        return !is_null($this->automapped_at);
    }

    public function isNotAutomapped(): bool
    {
        return !$this->isMapped();
    }

    public function getProcessingStateAttribute(): array
    {
        $status = $this->isMapped() || $this->isSchedule() ? 'completed' : 'processing';

        return compact('status');
    }
}
