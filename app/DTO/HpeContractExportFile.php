<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Spatie\DataTransferObject\DataTransferObject;

class HpeContractExportFile extends DataTransferObject implements Responsable
{
    public string $filePath;

    public ?string $fileName = null;

    public function __toString()
    {
        return $this->filePath;
    }

    public function toResponse($request)
    {
        return response()->download(
            $this->filePath,
            $this->fileName ?? basename($this->filePath)
        );
    }
}