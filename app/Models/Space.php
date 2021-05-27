<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Space
 *
 * @property string|null $space_name
 */
class Space extends Model
{
    use Uuid, SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
