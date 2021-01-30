<?php

namespace App\Services\DocumentEngine;

class ParseDistributorPDF extends Client
{
    protected function endpoint(): string
    {
        return 'v1/api/dist/pdf';
    }
}
