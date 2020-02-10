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
    public function initializeBelongsToUser()
    {
        $this->fillable = array_merge($this->fillable, ['user_id']);

        if (!app()->runningInConsole()) {
            static::replicating(function (Model $model) {
                $model->user_id = auth()->id();
            });
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentUser(Builder $query): Builder
    {
        return $query->where("{$this->getTable()}.user_id", request()->user()->id);
    }

    public function scopeCurrentUserWhen(Builder $query, $when): Builder
    {
        return $query->when($when, function ($query) {
            $query->currentUser();
        });
    }
}
