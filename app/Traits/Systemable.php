<?php namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Systemable
{
    public static function bootSystemable()
    {
        static::replicating(function (Model $model) {
            $model->is_system = false;
        });
    }

    public function isSystem()
    {
        return (bool) $this->getAttribute('is_system');
    }

    public function scopeSystem(Builder $query)
    {
        return $query->where('is_system', true);
    }
}
