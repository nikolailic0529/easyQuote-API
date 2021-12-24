<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateSupplierData extends DataTransferObject
{
    public ?string $supplier_name = null;

    public ?string $country_name = null;

    public ?string $contact_name = null;

    public ?string $contact_email = null;
}
