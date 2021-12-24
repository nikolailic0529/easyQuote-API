<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\{Uuid,};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Rennokki\QueryCache\Traits\QueryCacheable;

/**
 * @property string|null $name
 * @property string|null $code
 * @property string|null $symbol
 * @property ExchangeRate $exchangeRate
 * @property float|null $exchange_rate_value
 */
class Currency extends Model implements HasOrderedScope
{
    use Uuid, QueryCacheable;

    public $timestamps = false;

    protected $fillable = [
        'name', 'code', 'symbol'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`currencies`.`code`, ?, null) desc, `currencies`.`code`", [setting('base_currency')]);
    }

    public function exchangeRate(): HasOne
    {
        return $this->hasOne(ExchangeRate::class)->orderByDesc('date')->withDefault();
    }

    public function getLabelAttribute(): string
    {
        return "$this->symbol ($this->code)";
    }

    public function isSettingBaseCurrency(): bool
    {
        return $this->code === setting('base_currency');
    }

    public function isNotSettingBaseCurrency(): bool
    {
        return !$this->isSettingBaseCurrency();
    }
}
