<?php

namespace App\Domain\Template\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

class TemplateDataHeader extends DataTransferObject
{
    public string $key;

    public string $value;
}
