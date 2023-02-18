<?php

namespace App\Domain\Stats\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

class CustomerSummary extends DataTransferObject
{
    public string $company_id;
    public string $customer_name;

    public float $total_value;

    public int $total_count;

    public static function create($data, float $baseRate)
    {
        return new static([
            'total_value' => (float) data_get($data, 'total_value') * $baseRate,
            'total_count' => (int) data_get($data, 'total_count'),
            'customer_name' => data_get($data, 'customer_name'),
            'company_id' => data_get($data, 'company_id'),
        ]);
    }
}
