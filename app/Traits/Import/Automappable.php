<?php namespace App\Traits\Import;

trait Automappable
{
    public function markAsAutomapped()
    {
        return $this->forceFill([
            'automapped_at' => now()->toDateTimeString()
        ])->save();
    }

    public function markAsNotAutomapped()
    {
        return $this->forceFill([
            'automapped_at' => null
        ])->save();
    }

    public function isNotAutomapped()
    {
        return !isset($this->automapped_at);
    }
}
