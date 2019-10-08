<?php namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToUser
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentUser(Builder $query)
    {
        return $query->where(function ($query) {
            $query->where('is_system', true)
                ->orWhere('user_id', request()->user()->id);
        });
    }
}
