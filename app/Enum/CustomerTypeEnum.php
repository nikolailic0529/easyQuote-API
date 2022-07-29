<?php

namespace App\Enum;

enum CustomerTypeEnum: string
{
    case Prospect = 'Prospect';
    case NewCustomer = 'New customer';
    case ExistingCustomer = 'Existing customer';
}
