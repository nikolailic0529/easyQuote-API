<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class ReadBatchFileResult extends DataTransferObject
{
    public string $file_id;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\ReadAssetRow[]
     */
    public array $read_rows;
}
