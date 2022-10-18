<?php

namespace App\Services\Company\Exceptions;

use App\Models\Company;

class CompanyDataMappingException extends \Exception
{
    public static function defaultInvoiceAddressMissing(Company $company): static
    {
        return new static("Company [{$company->getIdForHumans()}] must have default invoice address.");
    }
}