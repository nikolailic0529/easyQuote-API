<?php

namespace App\Models\Data;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};

class ExchangeRate extends Model
{
    use Uuid, SoftDeletes;

    protected $fillable = [
        'country_id', 'currency_id', 'currency_code', 'exchange_rate', 'date', 'base_currency'
    ];

    protected $casts = [
        'exchange_rate' => 'float'
    ];

    protected $attributes = [
        'exchange_rate' => 1
    ];
}
