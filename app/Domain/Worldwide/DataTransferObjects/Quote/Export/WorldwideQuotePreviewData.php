<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class WorldwideQuotePreviewData extends DataTransferObject
{
    public TemplateData $template_data;

    public QuoteSummary $quote_summary;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\WorldwideDistributionData[]
     */
    public array $distributions;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetData[]|\App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetsGroupData[]
     */
    public array $pack_assets = [];

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\Export\AssetField[]
     */
    public array $pack_asset_fields = [];

    public string $asset_notes;

    public bool $pack_assets_are_grouped;

    /**
     * @Constraints\Choice({"Pack", "Contract"})
     */
    public string $contract_type_name;
}
