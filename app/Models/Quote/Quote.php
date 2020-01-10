<?php

namespace App\Models\Quote;

use App\Scopes\NonVersionScope;
use App\Traits\{
    NotifiableSubject,
    Quote\HasVersions
};

class Quote extends BaseQuote
{
    use HasVersions, NotifiableSubject;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new NonVersionScope);
    }
}
