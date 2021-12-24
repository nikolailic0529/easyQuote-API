<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUser
{
    protected static function bootBelongsToUser()
    {
        if (!app()->runningInConsole()) {
            static::replicating(function (Model $model) {
                $model->user_id = auth()->id();
            });
        }
    }

    public function initializeBelongsToUser()
    {
        $this->mergeFillable(['user_id']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentUser(Builder $query): Builder
    {
        return $query->where("{$this->getTable()}.user_id", auth()->id());
    }

    public function scopeCurrentUserWhen(Builder $query, $when): Builder
    {
        return $query->when($when, fn (Builder $query) => $query->currentUser());
    }
}
