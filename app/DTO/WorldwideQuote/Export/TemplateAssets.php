<?php

namespace App\DTO\WorldwideQuote\Export;

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
}
