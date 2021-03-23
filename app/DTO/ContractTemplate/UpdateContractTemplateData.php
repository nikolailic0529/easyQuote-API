<?php

namespace App\DTO\ContractTemplate;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateContractTemplateData extends DataTransferObject
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
     *
     * @var string
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

    public ?array $data_headers;

    public array $form_data;

    public bool $complete_design;
}
