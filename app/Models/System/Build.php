<?php

namespace App\Models\System;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Build extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'git_tag', 'build_number', 'maintenance_message', 'start_time', 'end_time'
    ];

    protected $dates = [
        'start_time', 'end_time'
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value);
    }
}
