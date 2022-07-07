<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;

interface EventHandler
{
    public function handle(IncomingWebhookData $data): void;
}