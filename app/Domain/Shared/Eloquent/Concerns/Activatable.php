<?php

namespace App\Domain\Shared\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Activatable
{
    protected static function bootActivatable(): void
    {
        static::creating(static function (Model $model): void {
            $model->setAttribute('activated_at', now()->toDateTimeString());
        });
    }

    public function initializeActivatable(): void
    {
        $this->observables = array_merge($this->observables, ['activating', 'deactivating']);
    }

    public function deactivate(): bool
    {
        $this->fireModelEvent('deactivating');

        if (is_null($this->activated_at)) {
            return false;
        }

        return tap($this->forceFill(['activated_at' => null])->save(), function ($deactivated) {
            activity()->on($this)->queueWhen('deactivated', $deactivated);
        });
    }

    public function activate(): bool
    {
        $this->fireModelEvent('activating');

        if (!is_null($this->activated_at)) {
            return false;
        }

        return tap(
            $this->forceFill(['activated_at' => now()])->save(),
            fn ($activated) => activity()->on($this)->queueWhen('activated', $activated)
        );
    }

    public function scopeActivated(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNotNull("{$this->getTable()}.activated_at"))
            ->limit(999999999);
    }

    public function scopeDeactivated(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull("{$this->getTable()}.activated_at"))
            ->limit(999999999);
    }

    public function scopeActivatedFirst(Builder $query): Builder
    {
        return $query->orderBy("{$this->getTable()}.activated_at", 'desc');
    }
}
