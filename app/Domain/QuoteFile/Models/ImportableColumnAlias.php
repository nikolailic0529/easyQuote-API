<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\QuoteFile\Concerns\{BelongsToImportableColumn};
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $alias
 */
class ImportableColumnAlias extends Model
{
    use Uuid;
    use BelongsToImportableColumn;

    public $timestamps = false;

    protected $guarded = [];
}
