<?php

namespace App\DTO\SalesOrder;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CancelSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $status_reason;
}
