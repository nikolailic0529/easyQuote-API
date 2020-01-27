<?php

namespace App\Models\Data;

use App\Models\BaseModel;

class ExchangeRate extends BaseModel
{
    protected $fillable = [
        'country_id', 'currency_id', 'currency_code', 'exchange_rate', 'date'
    ];

    protected $casts = [
        'exchange_rate' => 'float'
    ];
}
