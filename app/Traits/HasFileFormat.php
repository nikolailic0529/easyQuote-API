<?php

namespace App\Traits;

use App\Models\QuoteFile\QuoteFileFormat;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasFileFormat
{
    public function format(): BelongsTo
    {
        return $this->belongsTo(QuoteFileFormat::class, 'quote_file_format_id');
    }
}
