<?php

namespace App\Services\Exceptions;

class ServiceLookupRouteException extends \Exception
{
    public static function unsupportedVendorRoute(string $vendorName): static
    {
        return new static("Unsupported route for vendor: '$vendorName'.");
    }
}