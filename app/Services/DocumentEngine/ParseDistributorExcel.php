<?php

namespace App\Services\DocumentEngine;

class ParseDistributorExcel extends Client
{
    protected function endpoint(): string
    {
        return 'v1/api/dist/xls';
    }
}