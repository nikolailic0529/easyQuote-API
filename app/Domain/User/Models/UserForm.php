<?php

namespace App\Domain\User\Models;

use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserForm extends Model
{
    use Uuid;
    use Multitenantable;
    use BelongsToUser;
    use SoftDeletes;

    protected $fillable = [
        'key', 'form',
    ];

    protected $casts = [
        'form' => 'array',
    ];
}
