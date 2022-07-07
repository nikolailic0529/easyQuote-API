<?php

namespace App\Services\Pipeliner\Exceptions;

use App\Models\Address;
use JetBrains\PhpStorm\Pure;

class PipelinerSyncException extends \Exception
{
    #[Pure] public static function unsetPipeline(): static
    {
        return new static("Pipeline must be set.");
    }

    public static function missingAddressToContactRelation(Address $address): static
    {
        return new static("Address `{$address->getKey()}` must be associated with the contact.");
    }
}