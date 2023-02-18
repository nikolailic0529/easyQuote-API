<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class DistributionSupplierData extends DataTransferObject
{
    public string $supplier_name;

    public string $contact_name;

    public string $contact_email;

    public string $country_name;
}
