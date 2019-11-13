<?php

namespace App\Traits;

use Carbon\Carbon;

trait Handleable
{
    public function markAsHandled()
    {
        return $this->forceFill([
            'handled_at' => Carbon::now()->toDateTimeString(),
        ])->save();
    }

    public function markAsUnHandled()
    {
        return $this->forceFill([
            'handled_at' => null,
        ])->save();
    }

    public function isHandled()
    {
        return !is_null($this->handled_at);
    }

    public function scopeHandled($query)
    {
        return $query->whereNotNull('handled_at');
    }

    public function scopeUnHandled($query)
    {
        return $query->whereNull('handled_at');
    }
}
