<?php

namespace App\Services\WorldwideQuote\Models;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

final class QuoteExportResult implements Responsable
{
    public function __construct(
        public readonly string $content,
        public readonly string $filename,
    )
    {
    }

    public function toResponse($request): Response
    {
        return new Response($this->content, BaseResponse::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"$this->filename\"",
        ]);
    }
}