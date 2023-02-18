<?php

namespace App\Domain\Build\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Build extends Model
{
    use Uuid;
    use SoftDeletes;

    protected $fillable = [
        'git_tag', 'build_number', 'maintenance_message', 'start_time', 'end_time',
    ];

    protected $dates = [
        'start_time', 'end_time',
    ];
}
