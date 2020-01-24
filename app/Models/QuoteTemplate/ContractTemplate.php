<?php

namespace App\Models\QuoteTemplate;

use App\Scopes\ContractTemplateScope;

class ContractTemplate extends BaseQuoteTemplate
{
    protected $table = 'quote_templates';

    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array'
    ];

    protected $attributes = [
        'type' => QT_TYPE_CONTRACT
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ContractTemplateScope);
    }

    public function getItemNameAttribute()
    {
        return "Contract Template ({$this->name})";
    }
}
