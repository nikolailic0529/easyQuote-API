<?php

namespace App\DTO\SalesOrder;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $contract_template_id;

    /**
     * @Constraints\NotBlank(allowNull=true)
     *
     * @var string|null
     */
    public ?string $vat_number;

    /**
     * @Constraints\Choice({"EXEMPT", "NO VAT", "VAT Number"})
     *
     * @var string
     */
    public string $vat_type;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $customer_po;
}
