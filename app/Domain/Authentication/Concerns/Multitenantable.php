<?php

namespace App\Domain\Authentication\Concerns;

use Illuminate\Database\Eloquent\Model;

trait Multitenantable
{
    protected static function bootMultitenantable()
    {
        static::creating(function (Model $model) {
            if (is_null($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }
}
