<?php

namespace App\Domain\BusinessDivision\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BusinessDivision.
 *
 * @property string|null $id
 * @property string|null $division_name
 */
class BusinessDivision extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
