<?php

namespace App\Domain\QuoteFile\Concerns;

use App\Domain\QuoteFile\Models\ImportableColumn;
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
        return $query->whereHas('importableColumn', fn (Builder $query) => $query->where('is_temp', false));
    }
}
