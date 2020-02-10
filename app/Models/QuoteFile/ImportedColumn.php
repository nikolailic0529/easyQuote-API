<?php

namespace App\Models\QuoteFile;

use App\Models\BaseModel;
use App\Traits\{
    Draftable,
    BelongsToImportedRow,
    BelongsToImportableColumn,
    HasSystemScope
};
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportedColumn extends BaseModel
{
    use BelongsToImportableColumn, BelongsToImportedRow, Draftable, SoftDeletes, HasSystemScope;

    public $timestamps = false;

    protected $fillable = [
        'value', 'header', 'importable_column_id', 'imported_row_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'drafted_at',
        'deleted_at',
        'unknown_header',
        'importableColumn',
        'imported_row_id',
        'template_field_name'
    ];
}
