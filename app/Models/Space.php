<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Space
 *
 * @property string|null $space_name
 */
class Space extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
