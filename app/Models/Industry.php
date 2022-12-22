<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
