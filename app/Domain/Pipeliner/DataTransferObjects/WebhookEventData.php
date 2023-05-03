<?php

namespace App\Domain\Pipeliner\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class WebhookEventData extends DataTransferObject
{
    public string $event;
    public string $event_time;
    public string $team_space_id;
    public array $entity;
}
