<?php

namespace App\Domain\Company\Enum;

enum CustomerTypeEnum: string
{
    case Prospect = 'Prospect';
    case NewCustomer = 'New customer';
    case ExistingCustomer = 'Existing customer';
}
