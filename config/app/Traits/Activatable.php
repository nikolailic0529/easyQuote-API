<?php namespace App\Traits;

trait Activatable
{
    public function deactivate()
    {
        return $this->forceFill([
            'activated_at' => null
        ])->save();
    }

    public function activate()
    {
        return $this->forceFill([
            'activated_at' => now()->toDateTimeString()
        ])->save();
    }
}
