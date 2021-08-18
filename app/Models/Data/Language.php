<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use Uuid;

    protected $hidden = [
        'pivot', 'native_name', 'code'
    ];
}
