<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class SalesOrderCompanyData extends DataTransferObject
{
    public string $id;

    public string $name;

    public ?string $logo_url;
}
