<?php

namespace App\Traits;

trait Submittable
{
    public function submit(?array $submitted_data = null)
    {
        $fill = ['submitted_at' => now()->toDateTimeString()];

        if (filled($submitted_data)) {
            $fill = array_merge($fill, compact('submitted_data'));
        }

        return $this->forceFill($fill)->save();
    }

    public function unSubmit()
    {
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
}
