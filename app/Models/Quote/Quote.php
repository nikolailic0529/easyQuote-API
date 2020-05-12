<?php

namespace App\Models\Quote;

use App\Contracts\Multitenantable;
use App\Scopes\{
    QuoteTypeScope,
    NonVersionScope
};
use App\Traits\{
    Migratable,
    NotifiableModel,
    Quote\HasVersions,
    Quote\HasContract
};

class Quote extends BaseQuote implements Multitenantable
{
    use HasVersions, HasContract, NotifiableModel, Migratable;

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
