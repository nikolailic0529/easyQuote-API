<?php

namespace App\DTO\Template;

use Spatie\DataTransferObject\DataTransferObject;

final class TemplateElement extends DataTransferObject
{
    /**
     * @var \App\DTO\Template\TemplateElementChild[]
     */
    public array $children;

    public string $class = '';

    public string $css = '';
}
