<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Migratable
{
    public function markAsMigrated(string $column = 'migrated_at'): void
    {
        static::whereKey($this->getKey())->limit(1)->update([$column => now()]);
    }

    public function markAsNotMigrated(string $column = 'migrated_at'): void
    {
        static::whereKey($this->getKey())->limit(1)->update([$column => null]);
    }
    
    public function scopeMigrated(Builder $query, string $column = 'migrated_at'): Builder
    {
        return $query->whereNotNull($column);
    }

    public function scopeNotMigrated(Builder $query, string $column = 'migrated_at'): Builder
    {
        return $query->whereNull($column);
    }
}