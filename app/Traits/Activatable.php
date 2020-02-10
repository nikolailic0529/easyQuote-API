<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Activatable
{
    protected static function bootActivatable()
    {
        if (isset(static::$logAttributes)) {
            static::$logAttributes = array_merge(static::$logAttributes, ['activated_at']);
        }

        static::creating(function (Model $model) {
            $model->setAttribute('activated_at', now()->toDateTimeString());
        });
    }

    public function initializeActivatable()
    {
        $this->observables = array_merge($this->observables, ['activating', 'deactivating']);
    }

    public function deactivate(): bool
    {
        $this->fireModelEvent('deactivating');

        return $this->forceFill([
            'activated_at' => null
        ])->save();
    }

    public function activate(): bool
    {
        $this->fireModelEvent('activating');

        return $this->forceFill([
            'activated_at' => now()->toDateTimeString()
        ])->save();
    }

    public function scopeActivated(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereNotNull("{$this->getTable()}.activated_at");
        })->limit(999999999);
    }

    public function scopeDeactivated(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereNull("{$this->getTable()}.activated_at");
        })->limit(999999999);
    }

    public function scopeActivatedFirst(Builder $query): Builder
    {
        return $query->orderBy("{$this->getTable()}.activated_at", 'desc');
    }

    public function getActivatedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }
}
