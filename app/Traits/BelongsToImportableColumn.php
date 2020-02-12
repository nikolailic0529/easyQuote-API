<?php

namespace App\Traits;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToImportableColumn
{
    public function importableColumn(): BelongsTo
    {
        return $this->belongsTo(ImportableColumn::class);
    }

    public function scopeHasRegularColumn(Builder $query): Builder
    {
        return $query->whereHas('importableColumn', function (Builder $query) {
            $query->where('is_temp', false);
        });
    }
}
