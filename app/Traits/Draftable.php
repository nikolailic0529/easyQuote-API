<?php

namespace App\Traits;

trait Draftable
{
    public function markAsDrafted(): bool
    {
        $drafted = $this->forceFill(['drafted_at' => now()])->save();

        return $drafted;
    }

    public function markAsNotDrafted(): bool
    {
        return $this->forceFill([
            'drafted_at' => null,
        ])->save();
    }

    public function getDraftedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }
}
