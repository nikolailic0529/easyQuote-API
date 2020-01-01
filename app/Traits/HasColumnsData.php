<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportedColumn;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasColumnsData
{
    public function columnsData(): HasMany
    {
        return $this->hasMany(ImportedColumn::class);
    }
}
