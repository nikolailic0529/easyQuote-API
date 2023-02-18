<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class TemplateElementChild extends FlexibleDataTransferObject
{
    public string $id;

    public string $class = '';

    public string $css = '';

    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateElementChildControl[]
     */
    public array $controls;
}
