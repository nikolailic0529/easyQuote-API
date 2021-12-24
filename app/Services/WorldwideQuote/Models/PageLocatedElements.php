<?php

namespace App\Services\WorldwideQuote\Models;

use App\DTO\Template\TemplateElement;

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