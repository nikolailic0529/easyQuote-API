<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateContractTemplateData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $name;

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
     *
     * @var string[]
     */
    public array $countries;

    /**
     * @Constraints\Uuid
     */
    public ?string $currency_id;

    public array $form_data;
}
