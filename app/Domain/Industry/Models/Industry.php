<?php

namespace App\Domain\Industry\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
