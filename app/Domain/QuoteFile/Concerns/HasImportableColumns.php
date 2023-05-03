<?php

namespace App\Domain\QuoteFile\Concerns;

use App\Domain\QuoteFile\Models\ImportableColumn;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasImportableColumns
{
    public function importableColumns(): HasMany
    {
        return $this->hasMany(ImportableColumn::class);
    }
}
