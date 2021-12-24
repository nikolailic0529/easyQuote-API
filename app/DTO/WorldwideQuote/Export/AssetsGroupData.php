<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class AssetsGroupData extends DataTransferObject
{
    public string $group_name;

    /**
     * @var \App\DTO\WorldwideQuote\Export\AssetData[]
     */
    public array $assets;

    /**
     * @Constraints\NotBlank
     */
    public string $group_total_price;

    public float $group_total_price_float;
}
