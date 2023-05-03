<?php

namespace App\Domain\Pipeliner\Services\Webhook;

use App\Domain\Pipeliner\DataTransferObjects\IncomingWebhookData;
use App\Domain\Pipeliner\Models\PipelinerWebhook;
use App\Domain\Pipeliner\Models\PipelinerWebhookEvent;
use App\Domain\Pipeliner\Services\Webhook\EventHandlers\EventHandlerCollection;
use Illuminate\Database\ConnectionInterface;

class WebhookEventService
{
    public function __construct(protected ConnectionInterface $connection,
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

            $this->connection->transaction(static fn () => $notification->save());
        });
    }
}
