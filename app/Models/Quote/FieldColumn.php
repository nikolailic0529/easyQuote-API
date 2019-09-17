<?php namespace App\Models\Quote;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits \ {
    BelongsToQuote
};

class FieldColumn extends Pivot
{
    use BelongsToQuote;

    protected $hidden = [
        'quote_id'
    ];

    protected $table = 'quote_field_column';
}
