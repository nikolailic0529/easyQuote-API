<?php

namespace App\Services\DocumentEngine;

class ParseDistributorPDF extends Client
{
    protected function endpoint()
    {
        return 'v1/api/dist/pdf';
    }
}