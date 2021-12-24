<?php

namespace App\Models\QuoteFile;

use App\Traits\{
    BelongsToImportableColumn,
    Uuid
};
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $alias
 */
class ImportableColumnAlias extends Model
{
    use Uuid, BelongsToImportableColumn;

    public $timestamps = false;

    protected $guarded = [];
}
