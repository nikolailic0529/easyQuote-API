<?php namespace App\Models\Quote;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits \ {
    BelongsToQuote,
    BelongsToImportableColumn,
    BelongsToTemplateField
};

class FieldColumn extends Pivot
{
    use BelongsToQuote, BelongsToImportableColumn, BelongsToTemplateField;

    protected $table = 'quote_field_column';

    protected $hidden = [
        'quote_id', 'default_value'
    ];

    protected $attributes = [
        'importable_column_id' => null,
        'is_default_enabled' => false,
        'default_value' => null
    ];

    protected $casts = [
        'is_default_enabled' => 'boolean',
        'default_value' => 'string'
    ];
}
