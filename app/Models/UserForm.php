<?php

namespace App\Models;

use App\Traits\Auth\Multitenantable;
use App\Traits\BelongsToUser;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserForm extends Model
{
    use Uuid, Multitenantable, BelongsToUser, SoftDeletes;

    protected $fillable = [
        'key', 'form'
    ];

    protected $casts = [
        'form' => 'array'
    ];
}
