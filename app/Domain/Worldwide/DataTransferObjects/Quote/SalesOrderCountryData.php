<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class SalesOrderCountryData extends DataTransferObject
{
    public string $id;

    public string $name;

    public ?string $flag_url;
}
