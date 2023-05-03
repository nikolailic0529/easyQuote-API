<?php

namespace App\Domain\HpeContract\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

// TODO: add constraints for date_format in DTO.
final class HpeContractImportData extends DataTransferObject
{
    public ?string $date_format = null;
}
