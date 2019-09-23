<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteTemplate\QuoteTemplate,
    QuoteFile\ImportableColumn,
    Quote\FieldColumn
};
use App\Traits \ {
    BelongsToUser,
    BelongsToTemplateFieldType,
    BelongsToQuoteTemplates
};
use App\Contracts\HasOrderedScope;

class TemplateField extends UuidModel implements HasOrderedScope
{
    use BelongsToUser, BelongsToTemplateFieldType, BelongsToQuoteTemplates;

    protected $table = 'template_fields';

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at', 'activated_at', 'drafted_at', 'is_system', 'user_id',
        'template_field_type_id', 'templateFieldType', 'pivot'
    ];

    protected $appends = [
        'type'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_required' => 'boolean',
        'is_column' => 'boolean'
    ];

    public function quoteTemplates()
    {
        return $this->belongsToMany(QuoteTemplate::class);
    }

    public function fieldColumn()
    {
        return $this->hasOne(FieldColumn::class, 'template_field_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getTypeAttribute()
    {
        if(!isset($this->templateFieldType)) {
            return null;
        }

        return $this->templateFieldType->name;
    }
}
