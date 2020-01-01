<?php

namespace App\Models\Quote;

use App\Scopes\NonVersionScope;
use App\Traits\Quote\HasVersions;

class Quote extends BaseQuote
{
    use HasVersions;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new NonVersionScope);
    }
}
