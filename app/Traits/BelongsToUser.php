<?php namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent \ {
    Builder,
    Model
};

trait BelongsToUser
{
    public static function bootBelongsToUser()
    {
        static::replicating(function (Model $model) {
            $model->user_id = request()->user()->id;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentUser(Builder $query)
    {
        return $query->where(function ($query) {
            $query->where("{$this->getTable()}.user_id", request()->user()->id);
        });
    }
}
