<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteTemplate\QuoteTemplate,
    QuoteFile\ImportableColumn
};
use App\Traits \ {
    BelongsToUser
};

class TemplateField extends UuidModel
{
    use BelongsToUser;

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at', 'activated_at', 'drafted_at', 'is_system', 'user_id'
    ];

    public function quoteTemplates()
    {
        return $this->belongsToMany(QuoteTemplate::class);
    }

    public function importableColumn()
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_field_column', 'template_field_id');
    }
}
