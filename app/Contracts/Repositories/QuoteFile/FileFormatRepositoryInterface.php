<?php

namespace App\Contracts\Repositories\QuoteFile;

interface FileFormatRepositoryInterface
{
    /**
     * Get all available Quote File Formats
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all();

    /**
     * Find File Format by extensions array
     *
     * @param array $array
     * @return \App\Models\QuoteFile\QuoteFileFormat
     */
    public function whereInExtension(array $array);
}
