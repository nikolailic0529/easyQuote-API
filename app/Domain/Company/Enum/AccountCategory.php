<?php

namespace App\Domain\Company\Enum;

use App\Foundation\Support\Enum\Enum;

final class AccountCategory extends Enum
{
    const RESELLER = 'Reseller';
    const END_USER = 'End User';
    const BUSINESS_PARTNER = 'Business Partner';
}
