<?php

namespace App\Domain\Worldwide\DataTransferObjects\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateSupplierData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $supplier_id;

    public ?string $supplier_name = null;

    public ?string $country_name = null;

    public ?string $contact_name = null;

    public ?string $contact_email = null;
}
