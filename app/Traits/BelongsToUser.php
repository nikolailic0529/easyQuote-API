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
            $query->where("{$this->getTable()}.is_system", true)
                ->orWhere("{$this->getTable()}.user_id", request()->user()->id);
        });
    }
}
