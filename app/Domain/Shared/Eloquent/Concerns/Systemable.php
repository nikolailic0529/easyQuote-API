<?php

namespace App\Domain\Shared\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Systemable
{
    public static function bootSystemable(): void
    {
        static::replicating(static function (Model $model): void {
            $model->is_system = false;
        });
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }

    public function isNotSystem(): bool
    {
        return !$this->isSystem();
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
