<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class TemplateData extends DataTransferObject
{
    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateElement[]
     */
    public array $first_page_schema;

    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateElement[]
     */
    public array $assets_page_schema;

    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateElement[]
     */
    public array $payment_schedule_page_schema;

    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateElement[]
     */
    public array $last_page_schema;

    public TemplateAssets $template_assets;

    /**
     * @var array<string, string>
     */
    public array $headers = [];
}
