<?php namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait Activatable
{
    public static function bootActivatable()
    {
        static::creating(function (Model $model) {
            $model->setAttribute('activated_at', now()->toDateTimeString());
        });
    }

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

    public function scopeActivated($query)
    {
        return $query->where(function ($query) {
            $query->whereNotNull('activated_at')
                ->orWhereNull('user_id');
        });
    }

    public function scopeActivatedFirst($query)
    {
        return $query;
        return $query->orderBy("{$this->getTable()}.activated_at", 'desc');
    }
}
