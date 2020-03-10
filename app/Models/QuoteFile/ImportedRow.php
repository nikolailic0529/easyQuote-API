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
        'page', 'quote_file_id', 'user_id', 'columns_data', 'is_one_pay'
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

    public function setColumnsDataAttribute($value): void
    {
        if (is_string($value)) {
            $this->attributes['columns_data'] = collect(json_decode($value, true))->keyBy('importable_column_id')->toJson();
            return;
        }

        if (is_iterable($value)) {
            $this->attributes['columns_data'] = Collection::wrap($value)->keyBy('importable_column_id')->toJson();
        }
    }
}
