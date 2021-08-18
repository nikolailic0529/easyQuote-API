<?php

namespace App\Queries;

use App\Models\QuoteFile\QuoteFileFormat;
use Illuminate\Database\Eloquent\Builder;

class FileFormatQueries
{
    public function listOfFileFormatsQuery(): Builder
    {
        $model = new QuoteFileFormat();

        return $model->newQuery();
    }
}