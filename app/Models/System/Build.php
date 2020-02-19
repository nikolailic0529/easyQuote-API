<?php

namespace App\Models\System;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Build extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'git_tag', 'build_number', 'maintenance_message', 'start_time', 'end_time'
    ];
}
