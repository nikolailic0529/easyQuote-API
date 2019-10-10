<?php namespace App\Traits;

trait Submittable
{
    public function submit()
    {
        $this->forceFill([
            'submitted_at' => now()->toDateTimeString()
        ])->save();
    }

    public function unSubmit()
    {
        $this->forceFill([
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
