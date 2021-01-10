<?php

namespace App\Models\QuoteFile;

use App\Traits\{
    BelongsToImportableColumn,
    Uuid
};
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed importable_column_id
 * @property mixed|string id
 * @property \Illuminate\Support\Stringable|mixed alias
 */
class ImportableColumnAlias extends Model
{
    use Uuid, BelongsToImportableColumn;

    public $timestamps = false;

    protected $fillable = ['alias'];
}
