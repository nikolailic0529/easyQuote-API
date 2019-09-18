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
        'quote_id'
    ];
}
