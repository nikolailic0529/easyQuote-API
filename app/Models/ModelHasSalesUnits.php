<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ModelHasSalesUnits extends Pivot
{
    protected $table = 'model_has_sales_units';
}
