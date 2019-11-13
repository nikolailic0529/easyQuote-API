<?php

namespace App\Traits\Collaboration;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait BelongsToCollaboration
{
    public static function bootBelongsToCollaboration()
    {
        static::generated(function (Model $model) {
            if (isset($model->collaboration_id)) {
                return;
            }

            if ($model instanceof User) {
                $model->collaboration_id = $model->id;
                return;
            }

            if (!isset($model->user)) {
                return;
            }

            $model->collaboration_id = $model->user->collaboration_id;
        });

        static::replicating(function (Model $model) {
            $model->collaboration_id = request()->user()->collaboration_id;
        });
    }

    public function administrator()
    {
        return $this->belongsTo(User::class, 'collaboration_id');
    }

    public function scopeUserCollaboration($query)
    {
        return $query->where("{$this->getTable()}.collaboration_id", request()->user()->collaboration_id)
            ->orWhere("{$this->getTable()}.collaboration_id", null);
    }

    public function scopeUserCollaborationExcept($query)
    {
        return $query->where("{$this->getTable()}.collaboration_id", request()->user()->collaboration_id)
            ->where("{$this->getTable()}.id", '!=', request()->user()->id);
    }

    public function scopeCollaboration($query, string $collaboration_id)
    {
        return $query->where("{$this->getTable()}.collaboration_id", $collaboration_id);
    }
}
