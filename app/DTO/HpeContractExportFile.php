<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Spatie\DataTransferObject\DataTransferObject;

class HpeContractExportFile extends DataTransferObject implements Responsable
{
    public string $filePath;

    public ?string $fileName = null;

    public bool $stream = false;

    public function __toString()
    {
        return $this->filePath;
    }

    public function toResponse($request)
    {
        if ($this->stream) {
            return response()->file(
                $this->filePath
            );
        }

        return response()->download(
            $this->filePath,
            $this->fileName ?? basename($this->filePath)
        );
    }

    /** @return $this */
    public function stream()
    {
        $this->stream = true;

        return $this;
    }

    /** @return $this */
    public function download()
    {
        $this->stream = false;

        return $this;
    }
}