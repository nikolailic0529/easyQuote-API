<?php namespace App\Traits;

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
}
