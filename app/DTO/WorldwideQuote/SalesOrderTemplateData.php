<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class SalesOrderTemplateData extends DataTransferObject
{
    public string $id;

    public string $name;
}
