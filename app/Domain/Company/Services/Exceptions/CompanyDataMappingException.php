<?php

namespace App\Domain\Company\Services\Exceptions;

use App\Domain\Company\Models\Company;

class CompanyDataMappingException extends \Exception
{
    public static function defaultInvoiceAddressMissing(Company $company): static
    {
        return new static("Company [{$company->getIdForHumans()}] must have default invoice address.");
    }
}
