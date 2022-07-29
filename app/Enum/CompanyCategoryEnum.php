<?php

namespace App\Enum;

enum CompanyCategoryEnum: string
{
    case EndUser = 'End User';
    case Reseller = 'Reseller';
    case BusinessPartner = 'Business Partner';
    case Distributor = 'Distributor';
}