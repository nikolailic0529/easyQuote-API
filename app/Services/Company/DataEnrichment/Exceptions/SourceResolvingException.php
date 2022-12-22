<?php

namespace App\Services\Company\DataEnrichment\Exceptions;

use App\Models\Company;

class SourceResolvingException extends \Exception
{
    public static function missingInvoiceAddress(Company $company): static
    {
        return new static("Could not resolve data source: company [$company->name] doesnt have invoice address.");
    }

    public static function unsupportedCountry(string $country): static
    {
        return new static("Could not resolve data source: unsupported country [$country].");
    }
}