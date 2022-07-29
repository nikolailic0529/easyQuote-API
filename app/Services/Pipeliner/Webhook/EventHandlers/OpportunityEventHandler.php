<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Services\Pipeliner\PipelinerTouchEntityService;

class OpportunityEventHandler implements EventHandler
{
    public function __construct(protected PipelinerTouchEntityService $touchEntityService,
                                protected PipelinerGraphQlClient      $client)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        $this->touchEntityService->touchOpportunityById($data->entity['id']);
    }

    private function shouldBeHandled(IncomingWebhookData $data): bool
    {
        return in_array($data->event, [
            'Opportunity.DocumentLinked',
        ], true);
    }
}