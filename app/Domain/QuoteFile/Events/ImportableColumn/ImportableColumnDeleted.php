<?php

namespace App\Domain\QuoteFile\Events\ImportableColumn;

use App\Domain\Authentication\Contracts\WithCauserEntity;
use App\Domain\QuoteFile\Contracts\WithImportableColumnEntity;
use App\Domain\QuoteFile\Models\ImportableColumn;
use Illuminate\Database\Eloquent\Model;

final class ImportableColumnDeleted implements WithImportableColumnEntity, WithCauserEntity
{
    public function __construct(protected ImportableColumn $importableColumn,
                                protected ?Model $causer = null)
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
