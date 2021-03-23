<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ContractType
 * @property string|null $id
 * @property string|null $type_name
 * @property string|null $type_short_name
 */
class ContractType extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}
