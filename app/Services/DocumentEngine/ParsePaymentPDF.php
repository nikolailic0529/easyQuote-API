<?php

namespace App\Services\DocumentEngine;

class ParsePaymentPDF extends Client
{
    protected function endpoint(): string
    {
        return 'v1/api/payment/pdf';
    }
}
