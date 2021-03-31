<?php

namespace App\DTO\SalesOrder\Cancel;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CancelSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $sales_order_id;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $status_reason;
}
