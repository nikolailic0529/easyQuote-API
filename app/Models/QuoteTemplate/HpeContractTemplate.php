<?php

namespace App\Models\QuoteTemplate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HpeContractTemplate extends BaseQuoteTemplate
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
        'type' => QT_TYPE_HPE_CONTRACT
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(
            fn (Builder $builder) => $builder->where($builder->getModel()->qualifyColumn('type'), QT_TYPE_HPE_CONTRACT)
        );
    }

    public function getItemNameAttribute()
    {
        return "Contract Template ({$this->name})";
    }

    public static function dataHeadersDictionary(): array
    {
        return __('template.hpe_contract_data_headers');
    }
}
