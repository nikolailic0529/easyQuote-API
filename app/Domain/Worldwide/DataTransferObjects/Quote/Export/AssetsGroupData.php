<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class AssetsGroupData extends DataTransferObject
{
    public string $group_name;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetData[]
     */
    public array $assets;

    /**
     * @Constraints\NotBlank
     */
    public string $group_total_price;

    public float $group_total_price_float;
}
