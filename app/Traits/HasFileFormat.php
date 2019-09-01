<?php namespace App\Traits;

use App\Models\QuoteFile\QuoteFileFormat;

trait HasFileFormat
{
    public function format()
    {
        return $this->belongsTo(QuoteFileFormat::class, 'quote_file_format_id');
    }
}
