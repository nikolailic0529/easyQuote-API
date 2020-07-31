<?php

namespace App\Models\QuoteTemplate;

use App\Scopes\QuoteTemplateScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

        static::addGlobalScope(
            fn (Builder $builder) => $builder->where($builder->getModel()->qualifyColumn('type'), QT_TYPE_QUOTE)
        );
    }

    public function getItemNameAttribute()
    {
        return "Quote Template ({$this->name})";
    }

    public static function dataHeadersDictionary(): array
    {
        return __('template.quote_data_headers');
    }
}
