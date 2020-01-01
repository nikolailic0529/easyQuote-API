<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\{
    Builder,
    Model
};

trait Systemable
{
    public static function bootSystemable()
    {
        static::replicating(function (Model $model) {
            $model->is_system = false;
        });
    }

    public function isSystem(): bool
    {
        return (bool) $this->getAttribute('is_system');
    }

    public function scopeNonSystem(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }
}
