<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;

interface HasSalesUnit
{
    public function salesUnit(): HasOneThrough|HasOneDeep|BelongsTo;
}