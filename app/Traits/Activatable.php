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
            $query->whereNotNull("{$this->getTable()}.activated_at")
                ->orWhereNull("{$this->getTable()}.user_id");
        })->limit(999999999);
    }

    public function scopeDeactivated($query)
    {
        return $query->where(function ($query) {
            $query->whereNull("{$this->getTable()}.activated_at");
        })->limit(999999999);
    }

    public function scopeActivatedFirst($query)
    {
        return $query->orderBy("{$this->getTable()}.activated_at", 'desc');
    }
}
