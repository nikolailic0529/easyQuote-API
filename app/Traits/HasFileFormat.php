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

    public function isExcel()
    {
        return $this->isFormat(['xls', 'xlsx']);
    }

    public function isWord()
    {
        return $this->isFormat(['doc', 'docx']);
    }

    public function isPdf()
    {
        return $this->isFormat('pdf');
    }

    public function isCsv()
    {
        return $this->isFormat('csv');
    }

    protected function isFormat($ext)
    {
        if (!$this->propertyExists('format')) {
            return false;
        }

        $extension = $this->format->extension;

        if (gettype($ext) === 'array') {
            return in_array($extension, $ext);
        }

        return $extension === $ext;
    }
}
