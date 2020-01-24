<?php

namespace App\Traits\Auth;

use Illuminate\Database\Eloquent\Model;

trait Multitenantable
{
    protected static function bootMultitenantable()
    {
        if (auth()->check()) {
            static::creating(function (Model $model) {
                $model->user_id = auth()->id();
            });
        }
    }
}
