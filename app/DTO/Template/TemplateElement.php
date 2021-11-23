<?php

namespace App\DTO\Template;

use Spatie\DataTransferObject\DataTransferObject;

final class TemplateElement extends DataTransferObject
{
    protected array $exceptKeys = ['_hidden'];

    /**
     * @var \App\DTO\Template\TemplateElementChild[]
     */
    public array $children;

    public string $class = '';

    public string $css = '';

    public bool $toggle = false;

    public bool $visibility = false;

    public bool $_hidden = false;
}
