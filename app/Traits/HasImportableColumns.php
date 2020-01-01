<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasImportableColumns
{
    public function importableColumns(): HasMany
    {
        return $this->hasMany(ImportableColumn::class);
    }
}
