<?php

namespace App\Models\QuoteFile;

use App\Casts\SchemalessColumns;
use App\Traits\{
    BelongsToQuoteFile,
    Draftable,
    Selectable,
    Uuid
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};

class ImportedRow extends Model
{
    use Uuid, BelongsToQuoteFile, Draftable, Selectable, SoftDeletes;

    protected $fillable = [
        'id', 'page', 'quote_file_id','columns_data', 'is_one_pay'
    ];

    protected $hidden = [
        'quote_file',
        'user',
        'quote_file_id',
        'created_at',
        'updated_at',
        'drafted_at',
        'deleted_at',
        'page',
        'laravel_through_key'
    ];

    protected $casts = [
        'is_selected'   => 'boolean',
        'is_one_pay'    => 'boolean',
        'columns_data'  => SchemalessColumns::class
    ];

    protected $attributes = [
        'is_selected' => false
    ];
}
