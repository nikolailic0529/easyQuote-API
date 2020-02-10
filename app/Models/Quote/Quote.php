<?php

namespace App\Models\Quote;

use App\Scopes\{
    QuoteTypeScope,
    NonVersionScope
};
use App\Traits\{
    NotifiableSubject,
    Quote\HasVersions,
    Quote\HasContract
};

class Quote extends BaseQuote
{
    use HasVersions, HasContract, NotifiableSubject;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new NonVersionScope);
        static::addGlobalScope(new QuoteTypeScope);
    }

    protected $attributes = [
        'document_type' => Q_TYPE_QUOTE
    ];
}
