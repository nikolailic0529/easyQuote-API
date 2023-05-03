<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class SalesOrderCompanyData extends DataTransferObject
{
    public string $id;

    public string $name;

    public ?string $logo_url;
}
