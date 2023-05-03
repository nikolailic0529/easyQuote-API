<?php

namespace App\Domain\QuoteFile\Queries;

use App\Domain\QuoteFile\Models\QuoteFileFormat;
use Illuminate\Database\Eloquent\Builder;

class FileFormatQueries
{
    public function listOfFileFormatsQuery(): Builder
    {
        $model = new QuoteFileFormat();

        return $model->newQuery();
    }
}
