<?php

namespace App\Traits;

use Carbon\Carbon;

trait Draftable
{
    public function markAsDrafted()
    {
        return $this->forceFill([
            'drafted_at' => Carbon::now()->toDateTimeString()
        ])->save();
    }

    public function markAsNotDrafted()
    {
        return $this->forceFill([
            'drafted_at' => null
        ])->save();
    }
}