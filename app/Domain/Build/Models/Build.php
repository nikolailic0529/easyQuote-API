<?php

namespace App\Domain\Build\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property string|null $build_number
 * @property string|null $git_tag
 */
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
