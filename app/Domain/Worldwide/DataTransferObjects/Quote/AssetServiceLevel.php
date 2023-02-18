<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class AssetServiceLevel extends DataTransferObject
{
    public string $description;

    public float $price;

    public string $code;
}
