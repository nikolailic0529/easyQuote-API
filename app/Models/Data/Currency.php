<?php

namespace App\Models\Data;

use App\Contracts\HasOrderedScope;
use App\Traits\{
    Uuid,
    Currency\HasExchangeRate,
};
use Illuminate\Database\Eloquent\Model;
use Setting;

class Currency extends Model implements HasOrderedScope
{
    use Uuid, HasExchangeRate;

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

    public function isBaseCurrency(): bool
    {
        return app('exchange.service')->baseCurrency() === $this->code;
    }

    public function isNotBaseCurrency(): bool
    {
        return !$this->isBaseCurrency();
    }
}
