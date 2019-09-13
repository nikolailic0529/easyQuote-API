<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteTemplate\QuoteTemplate,
    QuoteFile\ImportableColumn
};
use App\Traits \ {
    BelongsToUser,
    BelongsToTemplateFieldType
};
use App\Contracts\HasOrderedScope;

class TemplateField extends UuidModel implements HasOrderedScope
{
    use BelongsToUser, BelongsToTemplateFieldType;

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at', 'activated_at', 'drafted_at', 'is_system', 'user_id',
        'template_field_type_id'
    ];

    public function quoteTemplates()
    {
        return $this->belongsToMany(QuoteTemplate::class);
    }

    public function importableColumn()
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_field_column', 'template_field_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
