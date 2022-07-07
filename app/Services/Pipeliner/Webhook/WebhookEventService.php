<?php

namespace App\Services\Pipeliner\Webhook;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Models\Pipeliner\PipelinerWebhook;
use App\Models\Pipeliner\PipelinerWebhookEvent;
use App\Services\Pipeliner\Webhook\EventHandlers\EventHandlerCollection;
use Illuminate\Database\ConnectionInterface;

class WebhookEventService
{
    public function __construct(protected ConnectionInterface    $connection,
                                protected EventHandlerCollection $handlers)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        $this->persistEvent($data);
        $this->handleEvent($data);
    }

    protected function handleEvent(IncomingWebhookData $data): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($data);
        }
    }

    protected function persistEvent(IncomingWebhookData $data): PipelinerWebhookEvent
    {
        return tap(new PipelinerWebhookEvent(), function (PipelinerWebhookEvent $notification) use ($data): void {
            $notification->webhook()->associate(PipelinerWebhook::query()->where('pl_reference', $data->webhook->id)->sole());
            $notification->event = $data->event;
            $notification->event_time = $data->event_time;
            $notification->payload = $data->payload;

            $this->connection->transaction(static fn() => $notification->save());
        });
    }
}