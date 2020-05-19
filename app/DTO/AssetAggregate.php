<?php

namespace App\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class AssetAggregate extends DataTransferObject
{
    public string $total_value;

    public int $total_count;

    public string $user_id;

    public function __construct(object $aggregate)
    {
        parent::__construct((array) $aggregate);
    }
}