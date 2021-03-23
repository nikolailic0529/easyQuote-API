<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class WorldwideQuotePreviewData extends DataTransferObject
{
    public TemplateData $template_data;

    public QuoteSummary $quote_summary;

    /**
     * @var \App\DTO\WorldwideQuote\Export\WorldwideDistributionData[]
     */
    public array $distributions;

    /**
     *
     * @var \App\DTO\WorldwideQuote\Export\AssetData[]
     */
    public array $pack_assets = [];

    /**
     * @var \App\DTO\WorldwideQuote\Export\AssetField[]
     */
    public array $pack_asset_fields = [];

    /**
     * @Constraints\Choice({"Pack", "Contract"})
     *
     * @var string
     */
    public string $contract_type_name;
}
