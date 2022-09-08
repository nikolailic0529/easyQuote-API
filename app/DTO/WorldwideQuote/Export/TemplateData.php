<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class TemplateData extends DataTransferObject
{
    /**
     * @var \App\DTO\Template\TemplateElement[]
     */
    public array $first_page_schema;

    /**
     * @var \App\DTO\Template\TemplateElement[]
     */
    public array $assets_page_schema;

    /**
     * @var \App\DTO\Template\TemplateElement[]
     */
    public array $payment_schedule_page_schema;

    /**
     * @var \App\DTO\Template\TemplateElement[]
     */
    public array $last_page_schema;

    public TemplateAssets $template_assets;

    /**
     * @var array<string, string>
     */
    public array $headers = [];
}
