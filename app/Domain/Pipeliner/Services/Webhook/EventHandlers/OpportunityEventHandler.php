<?php

namespace App\Domain\Pipeliner\Services\Webhook\EventHandlers;

use App\Domain\Pipeliner\DataTransferObjects\IncomingWebhookData;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerGraphQlClient;
use App\Domain\Pipeliner\Services\PipelinerTouchEntityService;

class OpportunityEventHandler implements EventHandler
{
    public function __construct(protected PipelinerTouchEntityService $touchEntityService,
                                protected PipelinerGraphQlClient $client)
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
