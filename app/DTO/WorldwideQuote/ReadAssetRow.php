<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class ReadAssetRow extends DataTransferObject
{
    public string $header;

    public string $header_key;

    public string $value;
}
