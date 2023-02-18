<?php

namespace App\Domain\QuoteFile\Concerns;

use App\Domain\QuoteFile\Models\QuoteFileFormat;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasFileFormat
{
    public function format(): BelongsTo
    {
        return $this->belongsTo(QuoteFileFormat::class, 'quote_file_format_id');
    }

    public function isExcel(): bool
    {
        return $this->isFormat(['xls', 'xlsx']);
    }

    public function isWord(): bool
    {
        return $this->isFormat(['doc', 'docx']);
    }

    public function isPdf(): bool
    {
        return $this->isFormat('pdf');
    }

    public function isCsv(): bool
    {
        return $this->isFormat('csv');
    }

    protected function isFormat($ext): bool
    {
        if (!isset($this->format)) {
            return false;
        }

        $extension = $this->format->extension;

        if (is_array($ext)) {
            return in_array($extension, $ext);
        }

        return $extension === $ext;
    }
}
