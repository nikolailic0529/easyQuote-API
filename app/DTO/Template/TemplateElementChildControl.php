<?php

namespace App\DTO\Template;

use Spatie\DataTransferObject\FlexibleDataTransferObject;

final class TemplateElementChildControl extends FlexibleDataTransferObject
{
    public string $id;

    public string $src = '';

    public string $name = '';

    public string $type = 'text';

    public string $label = '';

    public string $class = '';

    public string $css = '';

    public string $value = '';
}
