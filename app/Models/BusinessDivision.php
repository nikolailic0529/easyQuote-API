<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BusinessDivision
 * @property string|null $id
 * @property string|null $division_name
 */
class BusinessDivision extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
