<?php

namespace App\Domain\ExchangeRate\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $currency_id
 * @property string|null $country_id
 * @property string|null $currency_code
 * @property string|null $date
 * @property string|null $base_currency
 * @property float|null  $exchange_rate
 */
class ExchangeRate extends Model
{
    use Uuid;
    use SoftDeletes;

    protected $fillable = [
        'country_id', 'currency_id', 'currency_code', 'exchange_rate', 'date', 'base_currency',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
    ];

    protected $attributes = [
        'exchange_rate' => 1,
    ];
}
