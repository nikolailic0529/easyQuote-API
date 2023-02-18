<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class UpdateSalesOrderTemplateData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $user_id;

    /**
     * @Constraints\Uuid
     */
    public string $business_division_id;

    /**
     * @Constraints\Uuid
     */
    public string $contract_type_id;

    /**
     * @Constraints\Uuid
     */
    public string $company_id;

    /**
     * @Constraints\Uuid
     */
    public string $vendor_id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     */
    public array $country_ids;

    /**
     * @Constraints\Uuid
     */
    public ?string $currency_id;

    /**
     * @Constraints\NotBlank
     */
    public string $name;
}
