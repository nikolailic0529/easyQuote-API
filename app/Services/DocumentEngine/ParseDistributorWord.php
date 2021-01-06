<?php

namespace App\Services\DocumentEngine;

class ParseDistributorWord extends Client
{
    protected function endpoint()
    {
        return 'v1/api/dist/docx';
    }
}