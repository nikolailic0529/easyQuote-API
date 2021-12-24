<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class AssetServiceLevel extends DataTransferObject
{
    public string $description;

    public float $price;

    public string $code;
}
