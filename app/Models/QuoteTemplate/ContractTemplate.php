<?php

namespace App\Models\QuoteTemplate;

use App\Scopes\ContractTemplateScope;
use Illuminate\Database\Eloquent\Builder;

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

        static::addGlobalScope(
            fn (Builder $builder) => $builder->where($builder->getModel()->qualifyColumn('type'), QT_TYPE_CONTRACT)
        );
    }

    public function getItemNameAttribute()
    {
        return "Contract Template ({$this->name})";
    }

    public static function dataHeadersDictionary(): array
    {
        return __('template.contract_data_headers');
    }
}
