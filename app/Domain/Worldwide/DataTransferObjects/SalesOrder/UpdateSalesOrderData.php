<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $sales_order_template_id;

    /**
     * @Constraints\NotBlank(allowNull=true)
     */
    public ?string $vat_number;

    /**
     * @Constraints\Choice({"EXEMPT", "NO VAT", "VAT Number"})
     */
    public string $vat_type;

    /**
     * @Constraints\NotBlank
     */
    public string $customer_po;

    /**
     * @Constraints\NotBlank(allowNull=true)
     */
    public ?string $contract_number;
}
