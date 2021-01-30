<?php

namespace App\Services\DocumentEngine;

class ParseDistributorWord extends Client
{
    protected function endpoint(): string
    {
        return 'v1/api/dist/docx';
    }
}
