<?php

namespace App\DTO\Pipeliner;

use Spatie\DataTransferObject\DataTransferObject;

final class WebhookData extends DataTransferObject
{
    public string $id;
    public bool $insecure_ssl;
    public array $options;
    public string $url;
    public bool $is_deleted;
    public string $created;
    public string $modified;
    public string $application_id;
    public ?string $client_id;
    public array $events;
}