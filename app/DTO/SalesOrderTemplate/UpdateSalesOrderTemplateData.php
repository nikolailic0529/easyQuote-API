<?php

namespace App\DTO\SalesOrderTemplate;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class UpdateSalesOrderTemplateData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $user_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $business_division_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $contract_type_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $company_id;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $vendor_id;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $country_ids;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $currency_id;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $name;
}
