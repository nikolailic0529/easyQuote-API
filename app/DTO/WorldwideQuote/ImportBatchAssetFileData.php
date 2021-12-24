<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class ImportBatchAssetFileData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $file_id;

    public BatchAssetFileMapping $file_mapping;

    public bool $file_contains_headers = true;
}
