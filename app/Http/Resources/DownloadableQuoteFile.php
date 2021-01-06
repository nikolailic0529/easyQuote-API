<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as Http;

class DownloadableQuoteFile implements Responsable
{
    protected ?string $filePath;

    protected ?string $fileName;

    public function __construct(?string $filePath, ?string $fileName = null)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function toResponse($request)
    {
        if (! file_exists($this->filePath)) {
            return Response::json(['message' => 'The requested file not found.'], Http::HTTP_NOT_FOUND);
        }


        return Response::download(
            $this->filePath,
            $this->fileName,
        );
    }
}