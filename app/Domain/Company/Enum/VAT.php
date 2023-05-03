<?php

namespace App\Domain\Company\Enum;

final class VAT extends \App\Foundation\Support\Enum\Enum
{
    const EXEMPT = 'EXEMPT';
    const NO_VAT = 'NO VAT';
    const VAT_NUMBER = 'VAT Number';
}
