<?php

namespace App\Domain\QuoteFile\Contracts;

use App\Domain\QuoteFile\Models\ImportableColumn;

interface WithImportableColumnEntity
{
    public function getImportableColumn(): ImportableColumn;
}
