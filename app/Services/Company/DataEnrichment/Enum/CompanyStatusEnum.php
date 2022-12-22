<?php

namespace App\Services\Company\DataEnrichment\Enum;

enum CompanyStatusEnum: string
{
    case Active = 'active';
    case Dissolved = 'dissolved';
    case Liquidation = 'liquidation';
}
