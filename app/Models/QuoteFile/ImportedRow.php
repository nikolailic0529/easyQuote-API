<?php

namespace App\Models\QuoteFile;

use App\Models\BaseModel;
use App\Traits\{
    BelongsToUser,
    BelongsToQuoteFile,
    Draftable,
    Selectable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class ImportedRow extends BaseModel
{
    use BelongsToUser, BelongsToQuoteFile, Draftable, Selectable, SoftDeletes;

    protected $fillable = [
        'page', 'quote_file_id', 'user_id', 'columns_data'
    ];

    protected $hidden = [
        'quote_file',
        'user',
        'quote_file_id',
        'user_id',
        'created_at',
        'updated_at',
        'drafted_at',
        'deleted_at',
        'page',
        'laravel_through_key'
    ];

    protected $casts = [
        'is_selected'   => 'boolean',
        'columns_data'  => 'collection'
    ];

    protected $attributes = [
        'is_selected' => false
    ];

    public function getColumnsDataAttribute($value): Collection
    {
        return collect(json_decode($value));
    }
}
