<?php

namespace App\DTO\DocumentEngine;

use Spatie\DataTransferObject\DataTransferObject;

class DocumentEngineEventData extends DataTransferObject
{
    public string $event_reference;

    public array $event_payload;

    public ?string $causer_reference;
}