<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class ReadAssetRow extends DataTransferObject
{
    public string $header;

    public string $header_key;

    public string $value;
}
