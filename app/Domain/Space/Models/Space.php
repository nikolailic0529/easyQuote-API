<?php

namespace App\Domain\Space\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Space.
 *
 * @property string|null $space_name
 */
class Space extends Model
{
    use Uuid;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];
}
