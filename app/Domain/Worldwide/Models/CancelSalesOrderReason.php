<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CancelSalesOrderReason.
 *
 * @property string|null $description
 */
class CancelSalesOrderReason extends Model
{
    use Uuid;
    use SoftDeletes;

    protected $guarded = [];
}
