<?php

namespace App\Domain\Worldwide\Services\SalesOrder\Model;

final class InterpretedOrderStatus
{
    public function __construct(public int $status,
                                public ?string $reason = null)
    {
    }
}
