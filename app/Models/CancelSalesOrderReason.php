<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CancelSalesOrderReason
 *
 * @property string|null $description
 */
class CancelSalesOrderReason extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];
}
