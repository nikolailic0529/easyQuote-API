<?php

namespace App\Events\ImportableColumn;

use App\Contracts\WithCauserEntity;
use App\Contracts\WithImportableColumnEntity;
use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Database\Eloquent\Model;

final class ImportableColumnDeleted implements WithImportableColumnEntity, WithCauserEntity
{
    public function __construct(protected ImportableColumn $importableColumn,
                                protected ?Model           $causer = null)
    {
    }

    public function getImportableColumn(): ImportableColumn
    {
        return $this->importableColumn;
    }

    public function getCauser(): ?Model
    {
        return $this->causer;
    }
}