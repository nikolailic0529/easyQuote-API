<?php namespace App\Traits\Import;

trait Proccessable
{
    public function markAsProcessed()
    {
        return $this->forceFill([
            'processed_at' => now()->toDateTimeString()
        ])->save();
    }

    public function markAsNotProcessed()
    {
        return $this->forceFill([
            'processed_at' => null
        ])->save();
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeNotProcessed($query)
    {
        return $query->whereNull('processed_at');
    }
}
