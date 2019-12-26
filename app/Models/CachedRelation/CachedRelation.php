<?php

namespace App\Models\CachedRelation;

use Illuminate\Database\Eloquent\Model;

class CachedRelation extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $keyType = 'string';
}
