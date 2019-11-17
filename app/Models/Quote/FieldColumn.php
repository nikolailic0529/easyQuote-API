<?php

namespace App\Models\Quote;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\{
    BelongsToQuote,
    BelongsToImportableColumn,
    BelongsToTemplateField
};

class FieldColumn extends Pivot
{
    use BelongsToQuote, BelongsToImportableColumn, BelongsToTemplateField;

    public $timestamps = false;

    protected $table = 'quote_field_column';

    protected $hidden = [
        'quote_id', 'default_value'
    ];

    protected $attributes = [
        'importable_column_id' => null,
        'is_default_enabled' => false,
        'is_preview_visible' => true,
        'default_value' => null
    ];

    protected $casts = [
        'is_default_enabled' => 'boolean',
        'default_value' => 'string'
    ];

    public function defaultAttributesToArray(): array
    {
        return array_diff_key($this->getAttributes(), array_flip(['importable_column_id']));
    }
}
