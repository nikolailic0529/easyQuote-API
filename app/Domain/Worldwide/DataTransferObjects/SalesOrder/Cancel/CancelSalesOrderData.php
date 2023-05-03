<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder\Cancel;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CancelSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $sales_order_id;

    /**
     * @Constraints\NotBlank
     */
    public string $status_reason;
}
