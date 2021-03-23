<?php

namespace App\Services\DocumentEngine;

class ParseDistributorWorldwidePDF extends Client
{
    protected function endpoint(): string
    {
        return 'v1/api/ww-dist/pdf';
    }
}
