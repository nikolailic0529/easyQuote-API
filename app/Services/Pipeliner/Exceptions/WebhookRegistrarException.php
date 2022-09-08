<?php

namespace App\Services\Pipeliner\Exceptions;

class WebhookRegistrarException extends \Exception
{
    public static function webhookNotFound(string $reference): static
    {
        return new static("Webhook not found, reference: $reference");
    }
}