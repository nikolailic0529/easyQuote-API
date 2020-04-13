<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Illuminate\Support\Carbon;

class Build extends Model
{
    use Uuid, SoftDeletes;

    protected $fillable = [
        'git_tag', 'build_number', 'maintenance_message', 'start_time', 'end_time'
    ];

    protected $dates = [
        'start_time', 'end_time'
    ];
}
