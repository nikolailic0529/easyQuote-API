<?php

namespace App\Domain\VendorServices\Exceptions;

class ServiceLookupRouteException extends \Exception
{
    public static function unsupportedVendorRoute(string $vendorName): static
    {
        return new static("Unsupported route for vendor: '$vendorName'.");
    }
}
