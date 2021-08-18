<?php

namespace App\Contracts;

use App\Models\QuoteFile\ImportableColumn;

interface WithImportableColumnEntity
{
    public function getImportableColumn(): ImportableColumn;
}