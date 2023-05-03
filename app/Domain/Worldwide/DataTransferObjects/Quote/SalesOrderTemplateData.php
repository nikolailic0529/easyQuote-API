<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class SalesOrderTemplateData extends DataTransferObject
{
    public string $id;

    public string $name;
}
