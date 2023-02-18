<?php

namespace App\Domain\Company\Services\DataEnrichment\Enum;

enum CompanyStatusEnum: string
{
    case Active = 'active';
    case Dissolved = 'dissolved';
    case Liquidation = 'liquidation';
}
