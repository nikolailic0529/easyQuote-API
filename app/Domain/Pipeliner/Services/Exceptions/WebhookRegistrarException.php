<?php

namespace App\Domain\Pipeliner\Services\Exceptions;

class WebhookRegistrarException extends PipelinerException
{
    public static function webhookNotFound(string $reference): static
    {
        return new static("Webhook not found, reference: $reference");
    }
}
