<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class TemplateElement extends DataTransferObject
{
    protected array $exceptKeys = ['_hidden'];

    /**
     * @var \App\Domain\Template\DataTransferObjects\TemplateElementChild[]
     */
    public array $children;

    public string $class = '';

    public string $css = '';

    public bool $toggle = false;

    public bool $visibility = false;

    public bool $_hidden = false;
}
