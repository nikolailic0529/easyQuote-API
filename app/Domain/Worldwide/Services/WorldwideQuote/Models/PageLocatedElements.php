<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote\Models;

use App\Domain\Template\DataTransferObjects\TemplateElement;

/**
 * @property TemplateElement[] $bodyElements
 * @property TemplateElement[] $footerElements
 */
final class PageLocatedElements
{
    public function __construct(public readonly array $bodyElements,
                                public readonly array $footerElements)
    {
    }
}
