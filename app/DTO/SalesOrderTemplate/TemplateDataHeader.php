<?php

namespace App\DTO\SalesOrderTemplate;

use Spatie\DataTransferObject\DataTransferObject;

class TemplateDataHeader extends DataTransferObject
{
    public string $key;

    public string $value;
}
