<?php

namespace App\DTO\Pipeliner;

use Spatie\DataTransferObject\DataTransferObject;

final class IncomingWebhookData extends DataTransferObject
{
    public WebhookData $webhook;
    public string $event;
    public string $event_time;
    public string $team_space_id;
    public array $entity;
    public array $payload;

    protected bool $ignoreMissing = true;
}