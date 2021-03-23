<?php

namespace App\DTO\Template;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class TemplateElementChild extends FlexibleDataTransferObject
{
    public string $id;

    public string $class = '';

    public string $css = '';

    /**
     * @var \App\DTO\Template\TemplateElementChildControl[]
     */
    public array $controls;
}
