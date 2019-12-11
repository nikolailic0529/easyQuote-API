<?php

namespace App\Traits;

trait Submittable
{
    public function initializeSubmittable()
    {
        $this->observables = array_merge($this->observables, ['submitting', 'unsubmitting']);
    }

    public function submit(?array $submitted_data = null)
    {
        $this->fireModelEvent('submitting');

        $fill = ['submitted_at' => now()->toDateTimeString()];

        if (filled($submitted_data)) {
            $fill = array_merge($fill, compact('submitted_data'));
        }

        return $this->forceFill($fill)->save();
    }

    public function unSubmit()
    {
        $this->fireModelEvent('unsubmitting');

        return $this->forceFill([
            'submitted_data' => null,
            'submitted_at' => null
        ])->save();
    }

    public function scopeDrafted($query)
    {
        return $query->whereNull($this->getTable() . '.submitted_at');
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull($this->getTable() . '.submitted_at');
    }

    public function isSubmitted()
    {
        return !is_null($this->submitted_at);
    }

    public function getSubmittedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }
}
