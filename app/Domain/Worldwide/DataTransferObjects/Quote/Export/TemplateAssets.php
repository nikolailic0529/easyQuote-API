<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote\Export;

use Spatie\DataTransferObject\DataTransferObject;

final class TemplateAssets extends DataTransferObject
{
    /**
     * @var string[]
     */
    public array $logo_set_x1;

    /**
     * @var string[]
     */
    public array $logo_set_x2;

    /**
     * @var string[]
     */
    public array $logo_set_x3;

    public string $company_logo_x1;

    public string $company_logo_x2;

    public string $company_logo_x3;
}
