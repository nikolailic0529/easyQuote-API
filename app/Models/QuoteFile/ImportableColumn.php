<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumnAlias
};
use App\Contracts\HasOrderedScope;
use App\Traits\HasColumnsData;

class ImportableColumn extends UuidModel implements HasOrderedScope
{
    use HasColumnsData;

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function aliases()
    {
        return $this->hasMany(ImportableColumnAlias::class);
    }
}
