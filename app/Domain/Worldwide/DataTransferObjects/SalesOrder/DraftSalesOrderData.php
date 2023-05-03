<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class DraftSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $user_id;

    /**
     * @Constraints\Uuid
     */
    public string $worldwide_quote_id;

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
