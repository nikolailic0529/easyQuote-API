<?php

namespace App\Domain\Language\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use Uuid;

    protected $hidden = [
        'pivot', 'native_name', 'code',
    ];
}
