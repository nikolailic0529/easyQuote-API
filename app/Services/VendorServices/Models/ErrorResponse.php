<?php

namespace App\Services\VendorServices\Models;

use Spatie\DataTransferObject\DataTransferObject;

final class ErrorResponse extends DataTransferObject
{
    public string $ErrorCode;

    public string $ErrorDetails;
}