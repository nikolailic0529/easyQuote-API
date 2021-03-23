<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class ReadBatchFileResult extends DataTransferObject
{
    public string $file_id;

    /**
     * @var \App\DTO\WorldwideQuote\ReadAssetRow[]
     */
    public array $read_rows;
}
