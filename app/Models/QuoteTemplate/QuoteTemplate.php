<?php

namespace App\Models\QuoteTemplate;

use App\Scopes\QuoteTemplateScope;

class QuoteTemplate extends BaseQuoteTemplate
{
    protected $fillable = [
        'name', 'company_id', 'vendor_id', 'form_data'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'form_data' => 'array',
        'form_values_data' => 'array'
    ];

    protected $attributes = [
        'type' => QT_TYPE_QUOTE
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new QuoteTemplateScope);
    }

    public function getItemNameAttribute()
    {
        return "Quote Template ({$this->name})";
    }
}
