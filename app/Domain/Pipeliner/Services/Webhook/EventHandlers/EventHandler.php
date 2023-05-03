<?php

namespace App\Domain\Pipeliner\Services\Webhook\EventHandlers;

use App\Domain\Pipeliner\DataTransferObjects\IncomingWebhookData;

interface EventHandler
{
    public function handle(IncomingWebhookData $data): void;
}
