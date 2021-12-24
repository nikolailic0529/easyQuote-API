<?php

namespace App\Services\DocumentEngine\Concerns;

interface ParsesDocumentFile
{
    const HTTP_HEADERS = [
        'Accept-Encoding' => 'gzip',
    ];

    const OPT_FP = 'first_page';
    const OPT_LP = 'last_page';
    const OPT_EP = 'page';

    public function process(): ?array;

    public function filePath(string $filePath): static;

    public function page(int $exactPageNumber): static;

    public function firstPage(int $firstPageNumber): static;

    public function lastPage(int $lastPageNumber): static;
}