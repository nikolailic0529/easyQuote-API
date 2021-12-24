<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class DistributionSupplierData extends DataTransferObject
{
    public string $supplier_name;

    public string $contact_name;

    public string $contact_email;

    public string $country_name;
}
