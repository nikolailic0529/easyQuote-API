<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class CreateSupplierData extends DataTransferObject
{
    public ?string $supplier_name = null;

    public ?string $country_name = null;

    public ?string $contact_name = null;

    public ?string $contact_email = null;
}
