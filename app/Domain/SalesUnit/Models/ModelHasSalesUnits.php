<?php

namespace App\Domain\SalesUnit\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class ModelHasSalesUnits extends MorphPivot
{
    protected $table = 'model_has_sales_units';
}
