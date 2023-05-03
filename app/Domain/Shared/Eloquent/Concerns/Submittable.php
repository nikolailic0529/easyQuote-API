<?php

namespace App\Domain\Shared\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Submittable
{
    public function initializeSubmittable(): void
    {
        $this->observables = array_merge(
            $this->observables,
            ['submitting', 'unsubmitting', 'submitted', 'unsubmitted']
        );
    }

    public function submit(): bool
    {
        $this->fireModelEvent('submitting');

        if ($this->exists && !is_null($this->submitted_at)) {
            return false;
        }

        $pass = $this->forceFill(['submitted_at' => now()])->saveOrFail();

        activity()->on($this)->queue('submitted');

        $this->fireModelEvent('submitted', false);

        return $pass;
    }

    public function unSubmit(): bool
    {
        $this->fireModelEvent('unsubmitting');

        if (is_null($this->submitted_at)) {
            return false;
        }

        $pass = $this->forceFill(['submitted_at' => null])->saveOrFail();

        activity()->on($this)->queue('unravel');

        $this->fireModelEvent('unsubmitted');

        return $pass;
    }

    public function scopeDrafted(Builder $query): Builder
    {
        return $query->whereNull($this->getTable().'.submitted_at');
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereNotNull($this->getTable().'.submitted_at');
    }

    public function isSubmitted(): bool
    {
        return !is_null($this->submitted_at);
    }

    public function getSubmittedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_time'));
    }
}
