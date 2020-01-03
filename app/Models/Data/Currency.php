<?php

namespace App\Models\Data;

use App\Models\BaseModel;
use App\Contracts\HasOrderedScope;
use Setting;

class Currency extends BaseModel implements HasOrderedScope
{
    protected $appends = [
        'label'
    ];

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`currencies`.`code`, ?, null) desc", [Setting::get('base_currency')]);
    }

    public function getLabelAttribute()
    {
        return "{$this->symbol} ({$this->code})";
    }
}
