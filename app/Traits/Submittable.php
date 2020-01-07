<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Arr;

trait Submittable
{
    public function initializeSubmittable()
    {
        $this->observables = array_merge($this->observables, ['submitting', 'unsubmitting', 'submitted', 'unsubmitted']);
    }

    public function submit(?array $submitted_data = null): bool
    {
        $this->fireModelEvent('submitting');

        $fill = ['submitted_at' => now()->toDateTimeString()];

        if (filled($submitted_data)) {
            data_fill($fill, 'submitted_data', $submitted_data);
        }

        $pass = $this->forceFill($fill)->save();

        $this->fireModelEvent('submitted', false);

        return $pass;
    }

    public function unSubmit(): bool
    {
        $this->fireModelEvent('unsubmitting');

        $attributes = ['submitted_at' => null];

        if (Arr::has($this->getAttributes(), 'submitted_data')) {
            data_set($attributes, 'submitted_data', null);
        }

        $pass = $this->forceFill($attributes)->save();

        $this->fireModelEvent('unsubmitted');

        return $pass;
    }

    public function scopeDrafted(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.submitted_at');
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereNotNull($this->getTable() . '.submitted_at');
    }

    public function isSubmitted(): bool
    {
        return !is_null($this->submitted_at);
    }

    public function getSubmittedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }
}
