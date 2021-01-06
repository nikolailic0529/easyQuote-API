<?php

namespace App\Services\DocumentEngine;

class ParsePaymentPDF extends Client
{
    protected function endpoint()
    {
        return 'v1/api/payment/pdf';
    }
}