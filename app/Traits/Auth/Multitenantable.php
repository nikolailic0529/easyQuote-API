<?php

namespace App\Traits\Auth;

use Illuminate\Database\Eloquent\Model;

trait Multitenantable
{
    protected static function bootMultitenantable()
    {
        static::creating(function (Model $model) {
            if (auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }
}
