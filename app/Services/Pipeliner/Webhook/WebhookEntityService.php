<?php

namespace App\Services\Pipeliner\Webhook;

use App\Integrations\Pipeliner\Models\WebhookEntity;
use App\Models\Pipeliner\PipelinerWebhook;
use Illuminate\Database\ConnectionInterface;

class WebhookEntityService
{
    public function __construct(protected ConnectionInterface $connection)
    {
    }

    public function createWebhookFromPipelinerEntity(WebhookEntity $entity): PipelinerWebhook
    {
        return tap(new PipelinerWebhook(), function (PipelinerWebhook $webhook) use ($entity): void {
            $webhook->pl_reference = $entity->id;
            $webhook->url = $entity->url;
            $webhook->signature = $entity->signature;
            $webhook->insecure_ssl = $entity->insecureSsl;
            $webhook->events = $entity->events;
            $webhook->options = $entity->options;

            $this->connection->transaction(static fn () => $webhook->save());
        });
    }
}