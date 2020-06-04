<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\{
    Uuid,
    Currency\HasExchangeRate,
};
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;
use Setting;

class Currency extends Model implements HasOrderedScope
{
    use Uuid, HasExchangeRate, QueryCacheable;

    public $timestamps = false;

    protected $fillable = [
        'name', 'code', 'symbol'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`currencies`.`code`, ?, null) desc, `currencies`.`code`", [Setting::get('base_currency')]);
    }

    public function getLabelAttribute()
    {
        return "{$this->symbol} ({$this->code})";
    }

    public function isServiceBaseCurrency(): bool
    {
        return app('exchange.service')->baseCurrency() === $this->code;
    }

    public function isNotServiceBaseCurrency(): bool
    {
        return !$this->isServiceBaseCurrency();
    }

    public function isSettingBaseCurrency(): bool
    {
        return $this->code === setting('base_currency');
    }

    public function isNotSettingBaseCurrency(): bool
    {
        return !$this->isServiceBaseCurrency();
    }
}
