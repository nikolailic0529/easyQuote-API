<?php

namespace App\Domain\VendorServices\Services\Models;

use Spatie\DataTransferObject\DataTransferObject;

final class ErrorResponse extends DataTransferObject
{
    public string $ErrorCode;

    public string $ErrorDetails;
}
