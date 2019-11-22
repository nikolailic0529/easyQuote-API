<?php

namespace App\Models\Data;

use App\Models\UuidModel;
use App\Contracts\HasOrderedScope;
use Setting;

class Currency extends UuidModel implements HasOrderedScope
{
    protected $appends = [
        'label'
    ];

    public function scopeOrdered($query)
    {
        $base_currency = Setting::get('base_currency');
        return $query->orderByRaw("field(`currencies`.`code`, ?, null) desc", [$base_currency]);
    }

    public function getLabelAttribute()
    {
        return "{$this->symbol} ({$this->code})";
    }
}
